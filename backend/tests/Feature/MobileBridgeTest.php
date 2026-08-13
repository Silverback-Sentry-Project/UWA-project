<?php

namespace Tests\Feature;

use App\Models\Park;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery;
use Tests\TestCase;

class MobileBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // FirebaseService's constructor eagerly builds a real Kreait Factory/Auth client even
        // when a request never reaches code that calls it (e.g. the missing-token 401 short
        // circuit) - see the identical note in WebhookBridgeTest. Bind a relaxed mock as the
        // baseline; mockVerifiedToken() below replaces it with one that has real expectations
        // for tests that actually need verifyIdToken() to return something specific.
        $this->mock(FirebaseService::class);
    }

    public function test_mobile_incident_call_rejects_missing_token(): void
    {
        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'inc-1',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_mobile_incident_call_rejects_invalid_token(): void
    {
        $this->mockVerifiedToken(null, shouldThrow: true);

        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'inc-1',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ], ['Authorization' => 'Bearer not-a-real-token']);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_mobile_incident_call_returns_503_when_firebase_is_not_configured(): void
    {
        // Regression test for a real production bug: FirebaseService::auth() throws a plain
        // RuntimeException when no credentials are configured for this environment (see
        // FirebaseService::factory()). Before this was split into its own try/catch in
        // VerifyFirebaseIdToken, that exception was uncaught and every mobile bridge call
        // 500'd unconditionally on the hosted API, which had no FIREBASE_CREDENTIALS_JSON set.
        $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('auth')->andThrow(new \RuntimeException('Firebase credentials are not configured.'));
        });

        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'inc-1',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ], ['Authorization' => 'Bearer some-token']);

        $response->assertStatus(503);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_mobile_incident_call_upserts_with_valid_token(): void
    {
        $this->seedBridgePrerequisites();
        $this->mockVerifiedToken('mobile-reporter-uid');

        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'firestore-inc-mobile-001',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ], ['Authorization' => 'Bearer valid-token']);

        $response->assertOk();
        $this->assertDatabaseHas('incidents', [
            'firestore_doc_id' => 'firestore-inc-mobile-001',
            'source_system' => 'firestore',
        ]);
    }

    public function test_mobile_incident_call_ignores_client_supplied_identity_and_uses_verified_uid(): void
    {
        $reporter = User::create([
            'first_name' => 'Real',
            'last_name' => 'Reporter',
            'email' => 'real-reporter@test.local',
            'password_hash' => Hash::make('password'),
            'account_status' => 'Active',
            'firebase_uid' => 'genuine-verified-uid',
        ]);

        $impersonated = User::create([
            'first_name' => 'Someone',
            'last_name' => 'Else',
            'email' => 'someone-else@test.local',
            'password_hash' => Hash::make('password'),
            'account_status' => 'Active',
            'firebase_uid' => 'spoofed-client-claimed-uid',
        ]);

        Park::create([
            'park_name' => 'Bwindi Impenetrable National Park',
            'district' => 'Kanungu',
            'description' => 'Test park',
        ]);

        $this->mockVerifiedToken('genuine-verified-uid');

        $payload = array_merge($this->sampleIncidentPayload(), [
            'userId' => 'spoofed-client-claimed-uid',
        ]);

        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'firestore-inc-mobile-002',
            'eventType' => 'create',
            'after' => $payload,
        ], ['Authorization' => 'Bearer valid-token']);

        $response->assertOk();
        $this->assertDatabaseHas('incidents', [
            'firestore_doc_id' => 'firestore-inc-mobile-002',
            'reported_by' => $reporter->user_id,
        ]);
        $this->assertDatabaseMissing('incidents', [
            'firestore_doc_id' => 'firestore-inc-mobile-002',
            'reported_by' => $impersonated->user_id,
        ]);
    }

    public function test_mobile_incident_call_delete_removes_the_record(): void
    {
        $this->seedBridgePrerequisites();
        $this->mockVerifiedToken('mobile-reporter-uid');

        $this->postJson('/api/mobile/incidents', [
            'docId' => 'firestore-inc-mobile-003',
            'eventType' => 'create',
            'after' => $this->sampleIncidentPayload(),
        ], ['Authorization' => 'Bearer valid-token'])->assertOk();
        $this->assertDatabaseCount('incidents', 1);

        $response = $this->postJson('/api/mobile/incidents', [
            'docId' => 'firestore-inc-mobile-003',
            'eventType' => 'delete',
        ], ['Authorization' => 'Bearer valid-token']);

        $response->assertOk();
        $this->assertSoftDeleted('incidents', ['firestore_doc_id' => 'firestore-inc-mobile-003']);
    }

    private function mockVerifiedToken(?string $uid, bool $shouldThrow = false): void
    {
        $auth = Mockery::mock(Auth::class);

        if ($shouldThrow) {
            $auth->shouldReceive('verifyIdToken')->andThrow(new FailedToVerifyToken('Invalid token.'));
        } else {
            $claims = new DataSet(['sub' => $uid], '');

            $token = Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('claims')->andReturn($claims);

            $auth->shouldReceive('verifyIdToken')->andReturn($token);
        }

        $this->mock(FirebaseService::class, function ($mock) use ($auth) {
            $mock->shouldReceive('auth')->andReturn($auth);
        });
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
            'email' => 'bridge-mobile@test.local',
            'password_hash' => Hash::make('password'),
            'account_status' => 'Active',
            'firebase_uid' => 'mobile-reporter-uid',
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
            'reportedAt' => now()->toIso8601String(),
        ];
    }
}
