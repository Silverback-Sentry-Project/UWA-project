<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompensationClaim;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = CompensationClaim::with(['claimant', 'incident.park', 'incident.species', 'payment']);

        if ($request->filled('status')) {
            $query->where('claim_status', $request->status);
        }

        return response()->json(
            $query->latest('created_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function show(CompensationClaim $claim)
    {
        $claim->load(['claimant', 'incident.park', 'incident.species', 'documents', 'payment', 'reviewedBy', 'approvedBy']);

        return response()->json($claim);
    }

    public function approve(Request $request, CompensationClaim $claim)
    {
        $claim->update([
            'claim_status' => 'Approved',
            'approved_by' => $request->user()->user_id,
            'approved_at' => now(),
        ]);

        return response()->json($claim->fresh());
    }

    public function reject(Request $request, CompensationClaim $claim)
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $claim->update([
            'claim_status' => 'Rejected',
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
        ]);

        return response()->json($claim->fresh());
    }

    public function markPaid(Request $request, CompensationClaim $claim)
    {
        $validator = Validator::make($request->all(), [
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:Bank Transfer,Mobile Money,Cheque,Cash'],
            'transaction_reference' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($claim->claim_status !== 'Approved') {
            return response()->json(['message' => 'Only approved claims can be marked as paid.'], 422);
        }

        $payment = Payment::create([
            'claim_id' => $claim->claim_id,
            'amount_paid' => $request->amount_paid,
            'payment_method' => $request->payment_method,
            'transaction_reference' => $request->transaction_reference,
            'payment_date' => now(),
        ]);

        $claim->update(['claim_status' => 'Paid']);

        return response()->json($claim->fresh(['payment']));
    }
}
