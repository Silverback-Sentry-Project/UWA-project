<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RangerController extends Controller
{
    // A ranger belongs to a park (users.park_id). Gamepark accounts only
    // ever see rangers stationed at their own park; UWA sees all, optionally
    // filtered by ?park_id=.
    public function index(Request $request)
    {
        $query = User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))
            ->with('park')
            ->withCount([
                'incidentAssignments as active_assignments_count' => fn ($q) => $q->whereIn('assignment_status', ['Assigned', 'Accepted']),
            ]);

        if ($request->user()?->isGamepark()) {
            $query->where('park_id', $request->user()->park_id);
        } elseif ($request->filled('park_id')) {
            $query->where('park_id', $request->park_id);
        }

        $rangers = $query->get(['user_id', 'first_name', 'last_name', 'phone_number', 'email', 'account_status', 'park_id']);

        return response()->json($rangers);
    }
}
