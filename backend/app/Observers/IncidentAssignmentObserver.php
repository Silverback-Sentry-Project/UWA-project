<?php

namespace App\Observers;

use App\Models\IncidentAssignment;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use App\Support\SyncContext;

class IncidentAssignmentObserver
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly NotificationService $notifications,
    ) {}

    public function created(IncidentAssignment $assignment): void
    {
        $incident = $assignment->incident()->first();
        $ranger = $assignment->ranger()->first();

        // The ranger themselves gets the equivalent via the mobile FCM path once the
        // Firestore sync below lands - this notification is for the park's own portal
        // staff (an FYI that dispatch happened), which is why it's not gated on
        // SyncContext::$fromFirestore the way the Firestore sync-back below is.
        if ($incident !== null) {
            $this->notifications->notifyParkStaff(
                $incident->park_id,
                'Ranger assigned',
                ($ranger?->full_name ?? 'A ranger')." was assigned to incident #{$incident->incident_id}.",
                'Assignment',
            );
        }

        if (SyncContext::$fromFirestore) {
            return;
        }

        if (! $incident?->firestore_doc_id) {
            return;
        }

        $this->firebase->syncIncidentDocument($incident->firestore_doc_id, [
            'status' => 'assigned',
            'assignedTo' => $ranger?->firebase_uid,
            'assignedToName' => $ranger?->full_name,
        ]);
    }
}
