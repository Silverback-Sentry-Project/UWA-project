<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::with(['park', 'reporter', 'species', 'assignments.ranger']);

        if ($request->filled('park_id')) {
            $query->where('park_id', $request->park_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json(
            $query->latest('created_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function show(Incident $incident)
    {
        $incident->load([
            'park', 'reporter', 'species', 'media', 'assignments.ranger',
            'statusHistory.updatedBy', 'rangerReport',
        ]);

        return response()->json($incident);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reported_by' => ['required', 'exists:users,user_id'],
            'park_id' => ['required', 'exists:parks,park_id'],
            'incident_type' => ['required', 'in:Wildlife Sighting,Crop Damage,Livestock Loss,Property Damage,Human Injury,Human Fatality'],
            'description' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'village' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incident = Incident::create($validator->validated());

        return response()->json($incident, 201);
    }

    public function updateStatus(Request $request, Incident $incident)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:New,Assigned,In Progress,Resolved,Escalated'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $incident->status;
        $incident->update(['status' => $request->status]);

        IncidentStatusHistory::create([
            'incident_id' => $incident->incident_id,
            'updated_by' => $request->user()->user_id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'remarks' => $request->remarks,
        ]);

        return response()->json($incident->fresh(['statusHistory']));
    }

    public function assign(Request $request, Incident $incident)
    {
        $validator = Validator::make($request->all(), [
            'ranger_id' => ['required', 'exists:users,user_id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment = IncidentAssignment::create([
            'incident_id' => $incident->incident_id,
            'ranger_id' => $request->ranger_id,
            'assigned_by' => $request->user()->user_id,
            'assignment_status' => 'Assigned',
        ]);

        if ($incident->status === 'New') {
            $incident->update(['status' => 'Assigned']);
        }

        return response()->json($assignment->load('ranger'), 201);
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();

        return response()->json(null, 204);
    }
}
