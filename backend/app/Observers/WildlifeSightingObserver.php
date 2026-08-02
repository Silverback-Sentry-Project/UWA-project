<?php

namespace App\Observers;

use App\Models\WildlifeSighting;
use App\Services\FirebaseService;
use App\Support\SyncContext;

class WildlifeSightingObserver
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function updated(WildlifeSighting $sighting): void
    {
        if (SyncContext::$fromFirestore || ! $sighting->wasChanged(['approval_status'])) {
            return;
        }

        if (! $sighting->firestore_doc_id) {
            return;
        }

        $this->firebase->syncSightingDocument($sighting->firestore_doc_id, [
            'approval_status' => strtolower((string) $sighting->approval_status),
            'status' => strtolower((string) $sighting->approval_status),
        ]);
    }
}
