<?php

namespace App\Observers;

use App\Models\Incident;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use App\Support\SyncContext;

class IncidentObserver
{
    // Incident types treated as life-threatening for notification purposes - there's no
    // separate "Emergency"/"SOS" value in the incident_type enum (see
    // FirestoreSyncMapper::mapIncidentType(), which folds the mobile app's own EMERGENCY
    // type into this one), so this list plus is_escalated is what actually distinguishes an
    // urgent report from a routine one in this schema.
    private const LIFE_THREATENING_TYPES = ['Human Injury', 'Human Fatality'];

    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly NotificationService $notifications,
    ) {}

    // Notifies the reporting park's own staff (Warden/Gamepark Officer) - rangers already get
    // the equivalent via the mobile FCM path (see BRIDGE-CONTRACT.md), so this is purely for
    // the portal's own notification bell. Deliberately not gated on SyncContext::$fromFirestore
    // - that flag exists to stop Laravel's own writes echoing back to Firestore, which has
    // nothing to do with notifying portal staff about a new incident regardless of which side
    // (mobile bridge or portal) created it.
    public function created(Incident $incident): void
    {
        $isUrgent = $incident->is_escalated || in_array($incident->incident_type, self::LIFE_THREATENING_TYPES, true);

        $location = $incident->village ?: 'an unspecified location';

        $this->notifications->notifyParkStaff(
            $incident->park_id,
            $isUrgent ? 'Urgent incident reported' : 'New incident reported',
            "{$incident->incident_type} reported in {$location}.",
            $isUrgent ? 'SOS' : 'Incident',
        );
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
