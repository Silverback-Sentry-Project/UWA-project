<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SosAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SosAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = SosAlert::with(['reporter', 'park']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('park_id')) {
            $query->where('park_id', $request->park_id);
        }

        return response()->json(
            $query->latest('created_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function show(SosAlert $sosAlert)
    {
        return response()->json($sosAlert->load(['reporter', 'park']));
    }

    public function updateStatus(Request $request, SosAlert $sosAlert)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:Pending,Responding,Resolved'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sosAlert->update([
            'status' => $request->status,
            'resolved_at' => $request->status === 'Resolved' ? now() : $sosAlert->resolved_at,
        ]);

        return response()->json($sosAlert->fresh());
    }
}
