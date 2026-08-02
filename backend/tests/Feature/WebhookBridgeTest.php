<?php

namespace Tests\Feature;

use App\Models\Park;
use App\Models\User;
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
