<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvidenceForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvidenceFormController extends Controller
{
    // List all forms created by this gamepark's park.
    public function index(Request $request)
    {
        $forms = EvidenceForm::with(['fields', 'creator'])
            ->where('park_id', $request->user()->park_id)
            ->withCount('submissions')
            ->latest('updated_at')
            ->get();

        return response()->json($forms);
    }

    public function show(Request $request, EvidenceForm $form)
    {
        $this->authorizeSameParkOr404($request, $form);

        return response()->json($form->load(['fields', 'creator']));
    }

    // Google-Forms-style: title/description plus an ordered list of fields,
    // one of which can be an 'image' field for evidence photos.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Draft,Published'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.field_type' => ['required', 'in:text,textarea,number,date,select,image'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.is_required' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $form = DB::transaction(function () use ($request) {
            $form = EvidenceForm::create([
                'park_id' => $request->user()->park_id,
                'created_by' => $request->user()->user_id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->input('status', 'Draft'),
            ]);

            foreach ($request->input('fields') as $i => $field) {
                $form->fields()->create([
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'options' => $field['options'] ?? null,
                    'is_required' => $field['is_required'] ?? false,
                    'position' => $i,
                ]);
            }

            return $form;
        });

        return response()->json($form->load('fields'), 201);
    }

    public function update(Request $request, EvidenceForm $form)
    {
        $this->authorizeSameParkOr404($request, $form);

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Draft,Published'],
            'fields' => ['sometimes', 'array', 'min:1'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.field_type' => ['required_with:fields', 'in:text,textarea,number,date,select,image'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.is_required' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $form) {
            $form->update($request->only(['title', 'description', 'status']));

            if ($request->has('fields')) {
                // Simplest correct approach for an editable Google-Forms-style
                // structure: replace the field set with what was submitted.
                $form->fields()->delete();
                foreach ($request->input('fields') as $i => $field) {
                    $form->fields()->create([
                        'label' => $field['label'],
                        'field_type' => $field['field_type'],
                        'options' => $field['options'] ?? null,
                        'is_required' => $field['is_required'] ?? false,
                        'position' => $i,
                    ]);
                }
            }
        });

        return response()->json($form->fresh('fields'));
    }

    public function destroy(Request $request, EvidenceForm $form)
    {
        $this->authorizeSameParkOr404($request, $form);
        $form->delete();

        return response()->json(['message' => 'Form deleted.']);
    }

    private function authorizeSameParkOr404(Request $request, EvidenceForm $form): void
    {
        abort_if($form->park_id !== $request->user()->park_id, 404);
    }
}
