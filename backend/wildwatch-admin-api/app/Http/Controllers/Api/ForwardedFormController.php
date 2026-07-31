<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvidenceFormSubmission;
use Illuminate\Http\Request;

class ForwardedFormController extends Controller
{
    // Everything a gamepark has verified and forwarded, across all parks —
    // this is what shows up as a "new item" on the UWA portal.
    public function index(Request $request)
    {
        $query = EvidenceFormSubmission::with(['form', 'park', 'verifier', 'answers.field'])
            ->where('status', 'Forwarded');

        if ($request->filled('park_id')) {
            $query->where('park_id', $request->park_id);
        }

        return response()->json($query->latest('forwarded_at')->get());
    }

    public function show(EvidenceFormSubmission $submission)
    {
        abort_if($submission->status !== 'Forwarded', 404);

        return response()->json($submission->load(['form.fields', 'park', 'verifier', 'answers.field']));
    }
}
