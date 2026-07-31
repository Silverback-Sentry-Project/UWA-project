<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentStatusHistory;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentStatusHistory::with(['incident', 'updatedBy']);

        if ($request->user()?->isGamepark()) {
            $query->whereHas('incident', fn ($q) => $q->where('park_id', $request->user()->park_id));
        }

        return response()->json(
            $query->latest('updated_at')->paginate($request->integer('per_page', 50))
        );
    }
}
