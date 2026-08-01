<?php

namespace App\Observers;

use App\Models\CompensationClaim;
use App\Services\FirebaseService;
use App\Support\SyncContext;
use Illuminate\Support\Str;

class CompensationClaimObserver
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function updated(CompensationClaim $claim): void
    {
        if (SyncContext::$fromFirestore || ! $claim->wasChanged(['claim_status'])) {
            return;
        }

        $incident = $claim->incident()->first();
        if (! $incident?->firestore_doc_id) {
            return;
        }

        $this->firebase->syncIncidentDocument($incident->firestore_doc_id, [
            'claimStatus' => Str::lower($claim->claim_status),
        ]);
    }
}
