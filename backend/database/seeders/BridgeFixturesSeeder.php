<?php

namespace Database\Seeders;

use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Links Laravel users to their Firebase UID and assigned park using
 * seed-shared-fixtures.json, so the "same" dev account (e.g. ranger@wildwatch.app)
 * resolves to the same person on both sides of the bridge. Called from
 * DatabaseSeeder::run() - parks themselves are seeded there, not here.
 */
class BridgeFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $fixturesPath = dirname(__DIR__, 4).'/wildwatch-local-development-env-setup/seed-shared-fixtures.json';
        if (! file_exists($fixturesPath)) {
            $fixturesPath = database_path('fixtures/seed-shared-fixtures.json');
        }

        if (! file_exists($fixturesPath)) {
            $this->command?->warn('Shared seed fixtures not found — skipping BridgeFixturesSeeder');

            return;
        }

        /** @var array<string, mixed> $fixtures */
        $fixtures = json_decode(file_get_contents($fixturesPath), true, 512, JSON_THROW_ON_ERROR);

        $this->applyFixtures($fixtures);
    }

    /**
     * Split from run() so tests can exercise the resolution logic directly with a constructed
     * fixtures array, instead of having to mutate the real shared-fixtures file on disk.
     *
     * @param  array<string, mixed>  $fixtures
     */
    public function applyFixtures(array $fixtures): void
    {
        // Parks themselves are seeded by DatabaseSeeder (all 10, each with a firestore_id
        // matching this same fixtures file) - this seeder only needs to resolve each user's
        // fixture park_id (a Firestore slug, e.g. "queen-elizabeth") to the matching row.
        // Previously this only ever checked against the single fixtures['park'] entry, so a
        // user assigned to any park other than Bwindi silently never got a park_id set.
        foreach ($fixtures['users'] as $fixture) {
            $nameParts = explode(' ', $fixture['display_name'], 2);
            $user = User::firstOrCreate(
                ['email' => $fixture['email']],
                [
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'password_hash' => Hash::make($fixture['password']),
                    'account_status' => 'Active',
                    'email_verified' => true,
                ],
            );

            if (! empty($fixture['firebase_uid'])) {
                $user->update(['firebase_uid' => $fixture['firebase_uid']]);
            }

            foreach ($fixture['laravel_roles'] as $roleName) {
                $role = Role::where('role_name', $roleName)->first();
                if ($role && ! $user->roles->contains($role->role_id)) {
                    $user->roles()->attach($role->role_id);
                }
            }

            if (! empty($fixture['park_id'])) {
                $park = Park::where('firestore_id', $fixture['park_id'])->first();
                if ($park) {
                    $user->update(['park_id' => $park->park_id]);
                } else {
                    $this->command?->warn("Bridge fixtures: no park with firestore_id={$fixture['park_id']} for {$fixture['email']}");
                }
            }
        }

        $this->command?->info('Bridge fixtures seeded ('.count($fixtures['users']).' users)');
    }
}
