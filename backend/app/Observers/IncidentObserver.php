<?php

namespace App\Observers;

use App\Models\Incident;
use App\Services\FirebaseService;
use App\Support\SyncContext;

class IncidentObserver
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function updated(Incident $incident): void
    {
        if (SyncContext::$fromFirestore) {
            return;
        }

        if (! $incident->firestore_doc_id || ! $incident->wasChanged(['status'])) {
            return;
        }

        $this->firebase->syncIncidentDocument($incident->firestore_doc_id, [
            'status' => $this->mapStatusToFirestore($incident->status),
        ]);
    }

    private function mapStatusToFirestore(string $status): string
    {
        return match ($status) {
            'Assigned' => 'assigned',
            'In Progress' => 'in_progress',
            'Resolved' => 'resolved',
            'Escalated' => 'escalated',
            default => 'open',
        };
    }
}
