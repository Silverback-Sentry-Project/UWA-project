<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class RangerController extends Controller
{
    public function index()
    {
        $rangers = User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))
            ->withCount([
                'incidentAssignments as active_assignments_count' => fn ($q) => $q->whereIn('assignment_status', ['Assigned', 'Accepted']),
            ])
            ->get(['user_id', 'first_name', 'last_name', 'phone_number', 'email', 'account_status']);

        return response()->json($rangers);
    }
}
