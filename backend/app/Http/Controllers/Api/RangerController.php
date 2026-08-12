<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RangerController extends Controller
{
    public function index(Request $request)
    {
        $rangers = User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))
            ->when($request->filled('park_id'), fn ($q) => $q->where('park_id', $request->park_id))
            ->withCount([
                'incidentAssignments as active_assignments_count' => fn ($q) => $q->whereIn('assignment_status', ['Assigned', 'Accepted']),
            ])
            ->get(['user_id', 'first_name', 'last_name', 'phone_number', 'email', 'account_status', 'firebase_uid', 'park_id']);

        return response()->json($rangers);
    }
}
