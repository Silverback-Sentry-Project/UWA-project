<?php

namespace App\Observers;

use App\Models\IncidentAssignment;
use App\Services\FirebaseService;
use App\Support\SyncContext;

class IncidentAssignmentObserver
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function created(IncidentAssignment $assignment): void
    {
        if (SyncContext::$fromFirestore) {
            return;
        }

        $incident = $assignment->incident()->first();
        if (! $incident?->firestore_doc_id) {
            return;
        }

        $ranger = $assignment->ranger()->first();

        $this->firebase->syncIncidentDocument($incident->firestore_doc_id, [
            'status' => 'assigned',
            'assignedTo' => $ranger?->firebase_uid,
            'assignedToName' => $ranger?->full_name,
        ]);
    }
}
