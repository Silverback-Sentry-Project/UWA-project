<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompensationClaim;
use App\Models\Incident;
use App\Models\Park;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $gameparkParkId = $request->user()?->isGamepark() ? $request->user()->park_id : null;

        $incidentBase = fn () => $gameparkParkId ? Incident::where('park_id', $gameparkParkId) : Incident::query();
        $sosBase = fn () => $gameparkParkId ? SosAlert::where('park_id', $gameparkParkId) : SosAlert::query();
        $claimBase = fn () => $gameparkParkId
            ? CompensationClaim::whereHas('incident', fn ($q) => $q->where('park_id', $gameparkParkId))
            : CompensationClaim::query();
        $rangerBase = fn () => User::whereHas('roles', fn ($q) => $q->where('role_name', 'Ranger'))
            ->when($gameparkParkId, fn ($q) => $q->where('park_id', $gameparkParkId));

        return response()->json([
            'parks_count' => $gameparkParkId ? 1 : Park::count(),
            'rangers_count' => $rangerBase()->count(),
            'incidents' => [
                'total' => $incidentBase()->count(),
                'new' => $incidentBase()->where('status', 'New')->count(),
                'assigned' => $incidentBase()->where('status', 'Assigned')->count(),
                'in_progress' => $incidentBase()->where('status', 'In Progress')->count(),
                'resolved' => $incidentBase()->where('status', 'Resolved')->count(),
                'escalated' => $incidentBase()->where('status', 'Escalated')->count(),
            ],
            'sos_alerts' => [
                'total' => $sosBase()->count(),
                'pending' => $sosBase()->where('status', 'Pending')->count(),
                'responding' => $sosBase()->where('status', 'Responding')->count(),
            ],
            'claims' => [
                'total' => $claimBase()->count(),
                'submitted' => $claimBase()->where('claim_status', 'Submitted')->count(),
                'under_review' => $claimBase()->where('claim_status', 'Under Review')->count(),
                'approved' => $claimBase()->where('claim_status', 'Approved')->count(),
                'rejected' => $claimBase()->where('claim_status', 'Rejected')->count(),
                'paid' => $claimBase()->where('claim_status', 'Paid')->count(),
                'total_amount_estimated' => $claimBase()->sum('estimated_amount'),
            ],
            'recent_incidents' => $incidentBase()->with(['park', 'reporter'])
                ->latest('created_at')->limit(5)->get(),
        ]);
    }
}
