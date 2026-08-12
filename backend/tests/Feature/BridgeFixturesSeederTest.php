<?php

namespace Tests\Feature;

use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\BridgeFixturesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BridgeFixturesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_park_id_for_a_user_assigned_to_a_non_bwindi_park()
    {
        // Regression test for the bug fixed 2026-08-12: applyFixtures() used to only ever
        // resolve park_id against a single hardcoded park, so any fixture user assigned to a
        // park other than Bwindi silently never got a park_id set.
        Role::create(['role_name' => 'Ranger', 'description' => 'x']);
        Park::create(['park_name' => 'Bwindi Impenetrable National Park', 'firestore_id' => 'bwindi-impenetrable']);
        $queenElizabeth = Park::create(['park_name' => 'Queen Elizabeth National Park', 'firestore_id' => 'queen-elizabeth']);

        (new BridgeFixturesSeeder())->applyFixtures([
            'users' => [
                [
                    'email' => 'ranger@queen-elizabeth.test',
                    'password' => 'password123',
                    'display_name' => 'QE Ranger',
                    'firebase_uid' => 'uid-bridge-ranger-qe',
                    'firebase_role' => 'ranger',
                    'laravel_roles' => ['Ranger'],
                    'park_id' => 'queen-elizabeth',
                ],
            ],
        ]);

        $user = User::where('email', 'ranger@queen-elizabeth.test')->firstOrFail();
        $this->assertSame($queenElizabeth->park_id, $user->park_id);
        $this->assertSame('uid-bridge-ranger-qe', $user->firebase_uid);
        $this->assertTrue($user->roles->contains('role_name', 'Ranger'));
    }

    public function test_warns_but_does_not_fail_when_fixture_park_does_not_exist()
    {
        Role::create(['role_name' => 'Ranger', 'description' => 'x']);

        (new BridgeFixturesSeeder())->applyFixtures([
            'users' => [
                [
                    'email' => 'ranger@nowhere.test',
                    'password' => 'password123',
                    'display_name' => 'Nowhere Ranger',
                    'firebase_uid' => 'uid-bridge-ranger-nowhere',
                    'firebase_role' => 'ranger',
                    'laravel_roles' => ['Ranger'],
                    'park_id' => 'does-not-exist',
                ],
            ],
        ]);

        $user = User::where('email', 'ranger@nowhere.test')->firstOrFail();
        $this->assertNull($user->park_id);
    }

    public function test_database_seeder_wires_bridge_fixtures_with_working_roles()
    {
        $this->seed();

        $ranger = User::where('email', 'ranger@wildwatch.app')->first();
        $this->assertNotNull($ranger, 'ranger@wildwatch.app should be seeded by BridgeFixturesSeeder via DatabaseSeeder');
        $this->assertSame('uid-bridge-ranger-bwindi', $ranger->firebase_uid);
        $this->assertNotNull($ranger->park_id);

        $bwindi = Park::where('firestore_id', 'bwindi-impenetrable')->first();
        $this->assertNotNull($bwindi, 'parks should be seeded with firestore_id set');
        $this->assertSame($bwindi->park_id, $ranger->park_id);

        $warden = User::where('email', 'warden@wildwatch.app')->first();
        $this->assertNotNull($warden);
        // Regression check: 'Park Warden' previously was never seeded as a role at all, so
        // this attachment silently no-opped.
        $this->assertTrue($warden->roles->contains('role_name', 'Park Warden'));
    }

    public function test_database_seeder_correlates_one_incident_per_park_to_firestore()
    {
        $this->seed();

        $bwindi = Park::where('firestore_id', 'bwindi-impenetrable')->firstOrFail();
        $correlated = $bwindi->incidents()->where('firestore_doc_id', 'seed-bwindi-impenetrable-incident-1')->first();

        $this->assertNotNull($correlated);
        $this->assertSame('firestore', $correlated->source_system);
        $this->assertSame('New', $correlated->status);

        // The rest of that park's demo incidents stay Laravel-only, uncorrelated.
        $uncorrelatedCount = $bwindi->incidents()->whereNull('firestore_doc_id')->count();
        $this->assertGreaterThan(0, $uncorrelatedCount);
    }
}
