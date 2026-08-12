<?php

namespace Tests\Feature;

use App\Models\Park;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebhookBridgeTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-bridge-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.firebase_bridge.secret' => $this->secret]);

        // IncidentObserver/WildlifeSightingObserver take FirebaseService via constructor
        // injection unconditionally, even on writes that will hit their SyncContext guard and
        // never actually call it - so an 'update' eventType (which fires Eloquent's `updated`
        // event) would otherwise construct a real Firebase SDK client mid-test. Bind a relaxed
        // mock so this suite never depends on real/local Firebase credentials existing.
        $this->mock(FirebaseService::class);
    }

    public function test_incident_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/webhooks/incidents', [
            'docId' => 'inc-1',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_incident_webhook_upserts_with_valid_hmac(): void
    {
        $this->seedBridgePrerequisites();

        $payload = [
            'docId' => 'firestore-inc-001',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->secret);

        $response = $this->call(
            'POST',
            '/api/webhooks/incidents',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WildWatch-Signature' => $signature,
            ],
            $body,
        );

        $response->assertOk();
        $this->assertDatabaseHas('incidents', [
            'firestore_doc_id' => 'firestore-inc-001',
            'source_system' => 'firestore',
        ]);
    }

    public function test_incident_webhook_rejects_invalid_hmac(): void
    {
        $payload = [
            'docId' => 'inc-2',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ];

        $response = $this->postJson('/api/webhooks/incidents', $payload, [
            'X-WildWatch-Signature' => 'sha256=deadbeef',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_incident_webhook_update_upserts_the_existing_record_not_a_new_one(): void
    {
        $this->seedBridgePrerequisites();

        $this->signedPost('/api/webhooks/incidents', [
            'docId' => 'firestore-inc-002',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ])->assertOk();

        $response = $this->signedPost('/api/webhooks/incidents', [
            'docId' => 'firestore-inc-002',
            'eventType' => 'update',
            'after' => array_merge($this->sampleIncidentPayload(), [
                'status' => 'assigned',
                'summary' => 'Escalated: crop damage near village',
            ]),
        ]);

        $response->assertOk();
        // Same docId must upsert onto the one row, not create a second - that's what makes
        // this "upsert behavior" rather than just "insert behavior twice".
        $this->assertDatabaseCount('incidents', 1);
        $this->assertDatabaseHas('incidents', [
            'firestore_doc_id' => 'firestore-inc-002',
            'status' => 'Assigned',
            'description' => 'Escalated: crop damage near village',
        ]);
    }

    public function test_incident_webhook_delete_removes_the_record(): void
    {
        $this->seedBridgePrerequisites();

        $this->signedPost('/api/webhooks/incidents', [
            'docId' => 'firestore-inc-003',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ])->assertOk();
        $this->assertDatabaseCount('incidents', 1);

        $response = $this->signedPost('/api/webhooks/incidents', [
            'docId' => 'firestore-inc-003',
            'eventType' => 'delete',
        ]);

        $response->assertOk();
        // Incident soft-deletes now (see 2026_08_12_100000_add_soft_deletes_to_incidents_table)
        // - the row still exists with deleted_at set, it's just excluded from normal queries.
        $this->assertSoftDeleted('incidents', ['firestore_doc_id' => 'firestore-inc-003']);
    }

    public function test_webhook_returns_service_unavailable_when_secret_is_not_configured(): void
    {
        config(['services.firebase_bridge.secret' => null]);

        $response = $this->postJson('/api/webhooks/incidents', [
            'docId' => 'inc-4',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ]);

        $response->assertStatus(503);
        $this->assertDatabaseCount('incidents', 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedPost(string $uri, array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->secret);

        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WildWatch-Signature' => $signature,
            ],
            $body,
        );
    }

    private function seedBridgePrerequisites(): void
    {
        Park::create([
            'park_name' => 'Bwindi Impenetrable National Park',
            'district' => 'Kanungu',
            'description' => 'Test park',
        ]);

        User::create([
            'first_name' => 'Bridge',
            'last_name' => 'Tester',
            'email' => 'bridge@test.local',
            'password_hash' => Hash::make('password'),
            'account_status' => 'Active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleIncidentPayload(): array
    {
        return [
            'type' => 'conflict',
            'status' => 'open',
            'park' => 'Bwindi Impenetrable National Park',
            'summary' => 'Crop damage near village',
            'lat' => -1.05,
            'lng' => 29.7,
            'community' => 'Buhoma',
            'severity' => 'medium',
            'userId' => null,
            'reportedAt' => now()->toIso8601String(),
        ];
    }
}
