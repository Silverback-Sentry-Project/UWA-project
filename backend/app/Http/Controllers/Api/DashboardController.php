<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompensationClaim;
use App\Models\Incident;
use App\Models\Park;
use App\Models\SosAlert;
use App\Models\User;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'parks_count' => Park::count(),
            'rangers_count' => User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))->count(),
            'incidents' => [
                'total' => Incident::count(),
                'new' => Incident::where('status', 'New')->count(),
                'assigned' => Incident::where('status', 'Assigned')->count(),
                'in_progress' => Incident::where('status', 'In Progress')->count(),
                'resolved' => Incident::where('status', 'Resolved')->count(),
                'escalated' => Incident::where('status', 'Escalated')->count(),
            ],
            'sos_alerts' => [
                'total' => SosAlert::count(),
                'pending' => SosAlert::where('status', 'Pending')->count(),
                'responding' => SosAlert::where('status', 'Responding')->count(),
            ],
            'claims' => [
                'total' => CompensationClaim::count(),
                'submitted' => CompensationClaim::where('claim_status', 'Submitted')->count(),
                'under_review' => CompensationClaim::where('claim_status', 'Under Review')->count(),
                'approved' => CompensationClaim::where('claim_status', 'Approved')->count(),
                'rejected' => CompensationClaim::where('claim_status', 'Rejected')->count(),
                'paid' => CompensationClaim::where('claim_status', 'Paid')->count(),
                'total_amount_estimated' => CompensationClaim::sum('estimated_amount'),
            ],
            'recent_incidents' => Incident::with(['park', 'reporter'])
                ->latest('created_at')->limit(5)->get(),
        ]);
    }
}
