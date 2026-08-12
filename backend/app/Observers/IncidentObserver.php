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

        if (! $incident->firestore_doc_id || ! $incident->wasChanged(['status', 'is_escalated'])) {
            return;
        }

        $payload = [];

        if ($incident->wasChanged('status')) {
            $payload['status'] = $this->mapStatusToFirestore($incident->status);
        }

        if ($incident->wasChanged('is_escalated')) {
            $payload['isEscalated'] = $incident->is_escalated;
        }

        $this->firebase->syncIncidentDocument($incident->firestore_doc_id, $payload);
    }

    private function mapStatusToFirestore(string $status): string
    {
        return match ($status) {
            'Assigned' => 'assigned',
            'In Progress' => 'in_progress',
            'Resolved' => 'resolved',
            default => 'open',
        };
    }
}
