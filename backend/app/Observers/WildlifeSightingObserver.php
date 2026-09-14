<?php

namespace App\Observers;

use App\Models\WildlifeSighting;
use App\Services\FirebaseService;
use App\Support\SyncContext;

class WildlifeSightingObserver
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly \App\Services\MobileAlertService $mobileAlerts
    ) {}

    public function updated(WildlifeSighting $sighting): void
    {
        if (SyncContext::$fromFirestore || ! $sighting->wasChanged(['approval_status'])) {
            return;
        }

        if (! $sighting->firestore_doc_id) {
            return;
        }

        $approvalStatusStr = strtolower((string) $sighting->approval_status);
        
        $this->firebase->syncSightingDocument($sighting->firestore_doc_id, [
            'approval_status' => $approvalStatusStr,
            'status' => $approvalStatusStr,
        ]);

        if ($approvalStatusStr === 'approved' || $approvalStatusStr === 'resolved') {
            $reporterUid = $sighting->ranger?->firebase_uid;
            
            $title = "Sighting approved";
            $message = $sighting->notes ?: "Your wildlife sighting report has been approved.";
            
            $this->mobileAlerts->notifyReporterSightingApproved(
                $reporterUid,
                $sighting->firestore_doc_id,
                $title,
                $message
            );
        }
    }
}
