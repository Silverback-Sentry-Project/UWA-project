<?php

namespace Database\Seeders;

use App\Models\Park;
use App\Models\Role;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Community Member' => 'Reports incidents and tracks claims',
            'Ranger' => 'Responds to incidents',
            'Community Wildlife Officer' => 'Coordinates community wildlife activities',
            'Compensation Officer' => 'Reviews compensation claims',
            'UWA Official' => 'Approves claims and monitors analytics',
            'Park Warden' => 'Supervises park operations',
            'System Administrator' => 'Manages the system',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['role_name' => $name], ['description' => $description]);
        }

        $parks = [
            ['park_name' => 'Bwindi Impenetrable National Park', 'district' => 'Kanungu', 'description' => 'Mountain gorilla habitat'],
            ['park_name' => 'Mgahinga Gorilla National Park', 'district' => 'Kisoro', 'description' => 'Gorilla and golden monkey habitat'],
            ['park_name' => 'Queen Elizabeth National Park', 'district' => 'Kasese', 'description' => 'Savannah wildlife park'],
            ['park_name' => 'Murchison Falls National Park', 'district' => 'Masindi', 'description' => 'Largest national park in Uganda'],
        ];

        foreach ($parks as $park) {
            Park::firstOrCreate(['park_name' => $park['park_name']], $park);
        }

        $species = [
            ['common_name' => 'Elephant', 'scientific_name' => 'Loxodonta africana', 'conservation_status' => 'Vulnerable'],
            ['common_name' => 'Buffalo', 'scientific_name' => 'Syncerus caffer', 'conservation_status' => 'Least Concern'],
            ['common_name' => 'Lion', 'scientific_name' => 'Panthera leo', 'conservation_status' => 'Vulnerable'],
            ['common_name' => 'Mountain Gorilla', 'scientific_name' => 'Gorilla beringei beringei', 'conservation_status' => 'Endangered'],
        ];

        foreach ($species as $s) {
            Species::firstOrCreate(['common_name' => $s['common_name']], $s);
        }

        // Default admin account — CHANGE THIS PASSWORD after first login.
        $admin = User::firstOrCreate(
            ['email' => 'admin@uwa.go.ug'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password_hash' => Hash::make('Password123!'),
                'account_status' => 'Active',
                'email_verified' => true,
            ]
        );

        $adminRole = Role::where('role_name', 'System Administrator')->first();
        if (! $admin->roles->contains($adminRole->role_id)) {
            $admin->roles()->attach($adminRole->role_id);
        }
    }
}
