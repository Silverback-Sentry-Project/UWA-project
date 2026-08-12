<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvidenceFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvidenceFormSubmissionController extends Controller
{
    // Submissions filled by residents and sent to this gamepark's review queue.
    public function index(Request $request)
    {
        $query = EvidenceFormSubmission::with(['form', 'answers.field'])
            ->where('park_id', $request->user()->park_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest('created_at')->get());
    }

    public function show(Request $request, EvidenceFormSubmission $submission)
    {
        $this->authorizeSameParkOr404($request, $submission);

        return response()->json($submission->load(['form.fields', 'answers.field', 'verifier']));
    }

    // Gamepark reviews the evidence and marks it verified (or rejects it).
    public function verify(Request $request, EvidenceFormSubmission $submission)
    {
        $this->authorizeSameParkOr404($request, $submission);

        $validator = Validator::make($request->all(), [
            'decision' => ['required', 'in:verify,reject'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($submission->status !== 'Submitted') {
            return response()->json(['message' => 'Only submissions awaiting review can be verified or rejected.'], 409);
        }

        $submission->update([
            'status' => $request->decision === 'verify' ? 'Verified' : 'Rejected',
            'verified_by' => $request->user()->user_id,
            'verified_at' => now(),
            'verification_notes' => $request->notes,
        ]);

        return response()->json($submission->fresh(['form', 'answers.field', 'verifier']));
    }

    // Forward a verified submission to the main UWA portal for further action.
    public function forward(Request $request, EvidenceFormSubmission $submission)
    {
        $this->authorizeSameParkOr404($request, $submission);

        if ($submission->status !== 'Verified') {
            return response()->json(['message' => 'Only verified submissions can be forwarded to the UWA portal.'], 409);
        }

        $submission->update([
            'status' => 'Forwarded',
            'forwarded_at' => now(),
        ]);

        return response()->json($submission->fresh(['form', 'answers.field', 'verifier']));
    }

    private function authorizeSameParkOr404(Request $request, EvidenceFormSubmission $submission): void
    {
        abort_if($submission->park_id !== $request->user()->park_id, 404);
    }
}
