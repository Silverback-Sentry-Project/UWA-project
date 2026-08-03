<?php

namespace Database\Seeders;

use App\Models\Incident;
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
            'Public' => 'Community members and tourists',
            'Ranger' => 'Responds to incidents',
            'UWA Official' => 'Approves claims and monitors analytics',
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
            ['park_name' => 'Kibale National Park', 'district' => 'Kabarole', 'description' => 'Primate capital of the world'],
            ['park_name' => 'Semuliki National Park', 'district' => 'Bundibugyo', 'description' => 'Lowland tropical rainforest'],
            ['park_name' => 'Rwenzori Mountains National Park', 'district' => 'Kasese', 'description' => 'Glacial mountain range'],
            ['park_name' => 'Lake Mburo National Park', 'district' => 'Kiruhura', 'description' => 'Savannah and lake wildlife park'],
            ['park_name' => 'Kidepo Valley National Park', 'district' => 'Kaabong', 'description' => 'Remote semi-arid savannah park'],
            ['park_name' => 'Mount Elgon National Park', 'district' => 'Mbale', 'description' => 'Extinct volcano and caves'],
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

        $rangerRole = Role::where('role_name', 'Ranger')->first();

        foreach (Park::all() as $index => $park) {
            $slug = str($park->park_name)->before(' National Park')->slug('')->lower();

            for ($r = 1; $r <= 3; $r++) {
                $ranger = User::firstOrCreate(
                    ['email' => "ranger{$r}.{$slug}@wildwatch.app"],
                    [
                        'first_name' => "Ranger {$r}",
                        'last_name' => str($park->park_name)->before(' National Park')->value(),
                        'password_hash' => Hash::make('password123'),
                        'account_status' => 'Active',
                        'email_verified' => true,
                        'park_id' => $park->park_id,
                    ]
                );

                if (! $ranger->park_id) {
                    $ranger->update(['park_id' => $park->park_id]);
                }
                if (! $ranger->roles->contains($rangerRole->role_id)) {
                    $ranger->roles()->attach($rangerRole->role_id);
                }
            }
        }

        $official = User::firstOrCreate(
            ['email' => 'official@wildwatch.app'],
            [
                'first_name' => 'Bob',
                'last_name' => 'Official',
                'password_hash' => Hash::make('password123'),
                'account_status' => 'Active',
                'email_verified' => true,
            ]
        );

        $officialRole = Role::where('role_name', 'UWA Official')->first();
        if (! $official->roles->contains($officialRole->role_id)) {
            $official->roles()->attach($officialRole->role_id);
        }

        $this->seedIncidents($official);

        $admin = User::firstOrCreate(
            ['email' => 'admin@wildwatch.app'],
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

    private function seedIncidents(User $official): void
    {
        $incidentTypes = [
            'Crop Damage', 'Livestock Loss', 'Property Damage',
            'Wildlife Sighting', 'Human Injury',
        ];

        $statuses = ['New', 'Assigned', 'In Progress', 'Resolved', 'Escalated'];

        $locationTemplates = [
            'Kanungu' => [
                ['sub_county' => 'Kayonza', 'parish' => 'Buhoma', 'village' => 'Buhoma Village'],
                ['sub_county' => 'Kanyantorogo', 'parish' => 'Nkuringo', 'village' => 'Nkuringo'],
                ['sub_county' => 'Butogota', 'parish' => 'Rubona', 'village' => 'Rubona'],
            ],
            'Kisoro' => [
                ['sub_county' => 'buskimbiri', 'parish' => 'nteeko', 'village' => 'Nteeko'],
                ['sub_county' => 'nyabweishenya', 'parish' => 'rugongwe', 'village' => 'Rugongwe'],
                ['sub_county' => 'rubuguri_town_council', 'parish' => 'rushaaga', 'village' => 'Rushaaga'],
            ],
            'Kasese' => [
                ['sub_county' => 'Kasese Municipality', 'parish' => 'Nyamwamba', 'village' => 'Nyamwamba'],
                ['sub_county' => 'Katwe-Kabatoro', 'parish' => 'Katwe', 'village' => 'Katwe Village'],
                ['sub_county' => 'Hima', 'parish' => 'Ibanda-Kyanya', 'village' => 'Ibanda'],
            ],
            'Masindi' => [
                ['sub_county' => 'Masindi Municipality', 'parish' => 'Central', 'village' => 'Karujubu'],
                ['sub_county' => 'Pakanyi', 'parish' => 'Pakanyi', 'village' => 'Pakanyi Village'],
                ['sub_county' => 'Budongo', 'parish' => 'Nyabyeya', 'village' => 'Nyabyeya'],
            ],
            'Kabarole' => [
                ['sub_county' => 'Fort Portal City', 'parish' => 'Municipal', 'village' => 'Municipal Ward'],
                ['sub_county' => 'Bukuku', 'parish' => 'Bukuku', 'village' => 'Bukuku Village'],
                ['sub_county' => 'Ruteete', 'parish' => 'Ruteete', 'village' => 'Ruteete'],
            ],
            'Bundibugyo' => [
                ['sub_county' => 'Bundibugyo Town Council', 'parish' => 'Bundibugyo', 'village' => 'Bundibugyo'],
                ['sub_county' => 'Ntandi', 'parish' => 'Ntandi', 'village' => 'Ntandi Village'],
                ['sub_county' => 'Bubukwanga', 'parish' => 'Bubukwanga', 'village' => 'Bubukwanga'],
            ],
            'Kiruhura' => [
                ['sub_county' => 'rushasha', 'parish' => 'mirambiro', 'village' => 'Mirambiro'],
                ['sub_county' => 'rugaga', 'parish' => 'kashojwa', 'village' => 'Kashojwa'],
                ['sub_county' => 'kabingo', 'parish' => 'kyarugaju', 'village' => 'Kyarugaju'],
            ],
            'Kaabong' => [
                ['sub_county' => 'Kaabong Town Council', 'parish' => 'Kaabong', 'village' => 'Kaabong'],
                ['sub_county' => 'Karenga', 'parish' => 'Karenga', 'village' => 'Karenga Village'],
                ['sub_county' => 'Loyoro', 'parish' => 'Loyoro', 'village' => 'Loyoro'],
            ],
            'Mbale' => [
                ['sub_county' => 'Mbale City', 'parish' => 'Industrial Division', 'village' => 'Industrial Ward'],
                ['sub_county' => 'Budadiri', 'parish' => 'Budadiri', 'village' => 'Budadiri Village'],
                ['sub_county' => 'Bubulo', 'parish' => 'Bubulo', 'village' => 'Bubulo'],
            ],
        ];

        $parkCoords = [
            'Bwindi Impenetrable National Park' => [-1.05, 29.70],
            'Mgahinga Gorilla National Park' => [-1.37, 29.65],
            'Queen Elizabeth National Park' => [-0.20, 30.00],
            'Murchison Falls National Park' => [2.27, 31.77],
            'Kibale National Park' => [0.50, 30.40],
            'Semuliki National Park' => [0.85, 30.10],
            'Rwenzori Mountains National Park' => [0.38, 29.98],
            'Lake Mburo National Park' => [-0.61, 30.97],
            'Kidepo Valley National Park' => [3.92, 33.86],
            'Mount Elgon National Park' => [1.12, 34.17],
        ];

        $descriptions = [
            'Elephants raided a banana plantation overnight.',
            'Buffalo herd spotted near community farmland.',
            'Livestock killed by predators near park boundary.',
            'Crop damage reported by local farmer.',
            'Wildlife sighting reported by community member.',
            'Property fence damaged by wildlife.',
            'Human injury reported after wildlife encounter.',
            'Repeated crop raids in the last week.',
            'Community reported loud animal activity at night.',
            'Farmer lost several goats to wildlife.',
        ];

        foreach (Park::all() as $parkIndex => $park) {
            $district = $park->district;
            $locations = $locationTemplates[$district] ?? [
                ['sub_county' => 'Central', 'parish' => 'Central Parish', 'village' => 'Central Village'],
            ];
            [$baseLat, $baseLng] = $parkCoords[$park->park_name] ?? [0.0, 32.0];

            $incidentCount = 5 + ($parkIndex % 6);

            for ($i = 0; $i < $incidentCount; $i++) {
                $location = $locations[$i % count($locations)];

                Incident::firstOrCreate(
                    [
                        'park_id' => $park->park_id,
                        'description' => $descriptions[$i % count($descriptions)]." ({$park->park_name})",
                    ],
                    [
                        'reported_by' => $official->user_id,
                        'incident_type' => $incidentTypes[$i % count($incidentTypes)],
                        'latitude' => $baseLat + (($i * 0.01) - 0.02),
                        'longitude' => $baseLng + (($i * 0.01) - 0.02),
                        'village' => $location['village'],
                        'district' => $district,
                        'sub_county' => $location['sub_county'],
                        'parish' => $location['parish'],
                        'status' => $statuses[$i % count($statuses)],
                        'source_system' => 'laravel',
                    ]
                );
            }
        }
    }
}
