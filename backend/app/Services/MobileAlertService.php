<?php

namespace App\Services;

use App\Models\Park;
use Illuminate\Support\Str;

class MobileAlertService
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly FcmPushService $fcm
    ) {}

    public function notifyWardenAndPublic(string|int|null $parkId, string $incidentId, string $title, string $message): void
    {
        $parkTopicSuffix = $this->normalizeParkId($parkId);
        $wardenTopic = "warden_{$parkTopicSuffix}";
        $publicTopic = "park_alerts_{$parkTopicSuffix}";

        $this->writeNotificationDoc(null, 'SECURITY_ALERT', $title, $message, [
            'incidentId' => $incidentId,
            'topic' => $publicTopic,
        ]);

        $this->fcm->sendToTopic($wardenTopic, $title, $message, 'SECURITY_ALERT', ['incidentId' => $incidentId]);
        $this->fcm->sendToTopic($publicTopic, $title, $message, 'SECURITY_ALERT', ['incidentId' => $incidentId]);
    }

    public function notifyPublicSos(string|int|null $parkId, string $sosId, string $title, string $message): void
    {
        $parkTopicSuffix = $this->normalizeParkId($parkId);
        $topic = "park_alerts_{$parkTopicSuffix}";

        $this->writeNotificationDoc(null, 'SECURITY_ALERT', $title, $message, [
            'sosId' => $sosId,
            'topic' => $topic,
        ]);

        $this->fcm->sendToTopic($topic, $title, $message, 'SECURITY_ALERT', ['sosId' => $sosId]);
    }

    public function notifyReporterSightingApproved(?string $reporterUid, string $sightingId, string $title, string $message): void
    {
        if (!$reporterUid) {
            return;
        }

        $tokens = $this->firebase->fcmTokensFor($reporterUid);
        
        $this->writeNotificationDoc($reporterUid, 'SIGHTING_APPROVED', $title, $message, [
            'sightingId' => $sightingId,
        ]);

        $this->fcm->sendToTokens($tokens, $title, $message, 'SIGHTING_APPROVED', ['sightingId' => $sightingId]);
    }

    private function writeNotificationDoc(?string $targetUid, string $type, string $title, string $message, array $data): void
    {
        try {
            $this->firebase->firestore()->database()->collection('notifications')->add([
                'target_uid' => $targetUid,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'time' => now()->toIso8601String(),
                'isRead' => false,
                'data' => $data,
                'created_at' => \Kreait\Firebase\Firestore\FieldValue::serverTimestamp(),
            ]);
        } catch (\Throwable $e) {
            // Ignore for tests / failures
        }
    }

    private function normalizeParkId(mixed $parkIdentifier): string
    {
        if (!$parkIdentifier) {
            return 'unknown';
        }

        if (is_numeric($parkIdentifier)) {
            $park = Park::find($parkIdentifier);
            if ($park && $park->firestore_id) {
                $parkIdentifier = $park->firestore_id;
            } else {
                return 'unknown';
            }
        }

        return Str::of($parkIdentifier)
            ->replaceMatches('/([a-z])([A-Z])/', '$1_$2')
            ->replaceMatches('/[\s-]+/', '_')
            ->lower()
            ->value();
    }
}
