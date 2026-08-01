<?php

namespace Database\Seeders;

use App\Models\Park;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds bridge-test users whose emails/roles match seed-shared-fixtures.json.
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

        $parkName = $fixtures['park']['mysql_name'] ?? 'Bwindi Impenetrable National Park';
        $park = Park::firstOrCreate(
            ['park_name' => $parkName],
            [
                'district' => $fixtures['park']['district'] ?? 'Kanungu',
                'description' => 'Bridge seed park',
            ],
        );

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

            foreach ($fixture['laravel_roles'] as $roleName) {
                $role = Role::where('role_name', $roleName)->first();
                if ($role && ! $user->roles->contains($role->role_id)) {
                    $user->roles()->attach($role->role_id);
                }
            }
        }

        $this->command?->info("Bridge fixtures seeded (park_id={$park->park_id})");
    }
}
