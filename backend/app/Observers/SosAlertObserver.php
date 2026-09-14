<?php

namespace App\Observers;

use App\Models\SosAlert;
use App\Services\FirebaseService;
use App\Services\MobileAlertService;
use App\Services\NotificationService;
use App\Support\SyncContext;

class SosAlertObserver
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly NotificationService $notifications,
        private readonly MobileAlertService $mobileAlerts
    ) {}

    public function created(SosAlert $alert): void
    {
        $this->notifications->notifyParkStaff(
            $alert->park_id,
            'Urgent incident reported',
            "{$alert->emergency_type} reported in an unspecified location.",
            'SOS'
        );

        if (SyncContext::$fromFirestore) {
            return;
        }

        $title = "SOS alert";
        $message = $alert->description ?: "An SOS alert has been raised.";
        
        $this->mobileAlerts->notifyPublicSos(
            $alert->park_id,
            $alert->firestore_doc_id ?: (string) $alert->sos_id,
            $title,
            $message
        );
    }

    public function updated(SosAlert $alert): void
    {
        if (SyncContext::$fromFirestore) {
            return;
        }

        if (! $alert->firestore_doc_id || ! $alert->wasChanged(['status'])) {
            return;
        }

        $payload = [];
        if ($alert->wasChanged('status')) {
            $payload['status'] = strtolower($alert->status);
        }

        $this->firebase->firestore()->database()
            ->collection('sos_alerts')
            ->document($alert->firestore_doc_id)
            ->set(array_merge($payload, ['source_system' => 'laravel', 'updated_at' => new \DateTime()]), ['merge' => true]);
    }
}
