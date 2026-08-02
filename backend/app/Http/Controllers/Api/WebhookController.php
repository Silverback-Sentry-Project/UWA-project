<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\SosAlert;
use App\Models\WildlifeSighting;
use App\Services\FirestoreSyncMapper;
use App\Support\SyncContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function __construct(private readonly FirestoreSyncMapper $mapper)
    {
    }

    public function incidents(Request $request): JsonResponse
    {
        return $this->handleUpsert(
            $request,
            Incident::class,
            'firestore_doc_id',
            fn (string $docId, array $payload) => $this->mapper->mapIncidentAttributes($docId, $payload)
        );
    }

    public function sightings(Request $request): JsonResponse
    {
        return $this->handleUpsert(
            $request,
            WildlifeSighting::class,
            'firestore_doc_id',
            fn (string $docId, array $payload) => $this->mapper->mapSightingAttributes($docId, $payload)
        );
    }

    public function sosAlerts(Request $request): JsonResponse
    {
        return $this->handleUpsert(
            $request,
            SosAlert::class,
            'firestore_doc_id',
            fn (string $docId, array $payload) => $this->mapper->mapSosAlertAttributes($docId, $payload)
        );
    }

    /**
     * @param  class-string  $modelClass
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $mapAttributes
     */
    private function handleUpsert(
        Request $request,
        string $modelClass,
        string $externalKey,
        callable $mapAttributes
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'docId' => ['required', 'string'],
            'eventType' => ['required', 'in:create,update,delete'],
            'before' => ['nullable', 'array'],
            'after' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $docId = $request->string('docId')->value();
        $eventType = $request->string('eventType')->value();
        $payload = $request->input('after') ?? $request->input('before') ?? [];

        if ($eventType === 'delete') {
            SyncContext::$fromFirestore = true;

            try {
                $modelClass::where($externalKey, $docId)->delete();
            } finally {
                SyncContext::$fromFirestore = false;
            }

            return response()->json(['message' => 'Deleted', 'docId' => $docId]);
        }

        if (! is_array($payload) || $payload === []) {
            return response()->json(['message' => 'Missing document payload.'], 422);
        }

        $attributes = $mapAttributes($docId, $payload);

        SyncContext::$fromFirestore = true;

        try {
            $record = DB::transaction(function () use ($modelClass, $externalKey, $docId, $attributes) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = $modelClass::updateOrCreate(
                    [$externalKey => $docId],
                    $attributes
                );

                return $model->fresh();
            });
        } finally {
            SyncContext::$fromFirestore = false;
        }

        return response()->json($record);
    }
}
