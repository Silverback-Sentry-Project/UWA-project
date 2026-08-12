<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Park;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * IncidentObserver::updated() is the Laravel-side mirror of Cloud Functions' shouldSkipBridge():
 * it must NOT echo a webhook-originated status/escalation change back to Firestore (infinite
 * bounce), but MUST still forward a genuine ranger/warden-initiated change. Only the "must not
 * echo" half was previously exercised implicitly (via the create-path webhook test, which never
 * fires the `updated` event at all) - this file tests both halves directly.
 */
class IncidentObserverTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-bridge-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.firebase_bridge.secret' => $this->secret]);
    }

    public function test_does_not_echo_back_to_firestore_when_the_update_came_from_the_webhook(): void
    {
        $incident = $this->seedIncidentWithFirestoreDocId();

        $firebase = $this->mock(FirebaseService::class);
        $firebase->shouldNotReceive('syncIncidentDocument');

        $payload = [
            'docId' => $incident->firestore_doc_id,
            'eventType' => 'update',
            'after' => [
                'type' => 'conflict',
                'status' => 'assigned',
                'park' => 'Bwindi Impenetrable National Park',
                'summary' => $incident->description,
                'lat' => (float) $incident->latitude,
                'lng' => (float) $incident->longitude,
            ],
        ];
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->secret);

        $response = $this->call(
            'POST',
            '/api/webhooks/incidents',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WildWatch-Signature' => $signature],
            $body,
        );

        $response->assertOk();
        $this->assertSame('Assigned', $incident->fresh()->status);
    }

    public function test_echoes_to_firestore_when_a_ranger_initiated_update_changes_status(): void
    {
        $incident = $this->seedIncidentWithFirestoreDocId();

        $firebase = $this->mock(FirebaseService::class);
        $firebase->shouldReceive('syncIncidentDocument')
            ->once()
            ->with($incident->firestore_doc_id, ['status' => 'assigned']);

        // No SyncContext involved here - this is what a normal (non-webhook) controller action
        // updating incident status looks like from the model's perspective.
        $incident->update(['status' => 'Assigned']);
    }

    public function test_does_not_sync_when_status_and_escalation_are_unchanged(): void
    {
        $incident = $this->seedIncidentWithFirestoreDocId();

        $firebase = $this->mock(FirebaseService::class);
        $firebase->shouldNotReceive('syncIncidentDocument');

        // Touches an unrelated column only - status/is_escalated are the only two fields the
        // observer forwards, so this should be a no-op for it.
        $incident->update(['village' => 'A Different Village']);
    }

    private function seedIncidentWithFirestoreDocId(): Incident
    {
        $park = Park::create([
            'park_name' => 'Bwindi Impenetrable National Park',
            'district' => 'Kanungu',
            'description' => 'Test park',
            'firestore_id' => 'bwindi-impenetrable',
        ]);

        $user = User::create([
            'first_name' => 'Bridge',
            'last_name' => 'Tester',
            'email' => 'bridge-observer@test.local',
            'password_hash' => Hash::make('password'),
            'account_status' => 'Active',
        ]);

        return Incident::create([
            'reported_by' => $user->user_id,
            'park_id' => $park->park_id,
            'incident_type' => 'Conflict',
            'description' => 'Crop damage near village',
            'latitude' => -1.05,
            'longitude' => 29.7,
            'status' => 'New',
            'is_escalated' => false,
            'firestore_doc_id' => 'firestore-inc-observer-1',
            'source_system' => 'firestore',
        ]);
    }
}
