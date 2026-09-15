<?php

namespace Database\Seeders;

use App\Models\Park;
use App\Models\Role;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Public' => 'Community members and tourists',
            'Ranger' => 'Responds to incidents',
            'UWA Official' => 'Approves claims and monitors analytics',
            'System Administrator' => 'Manages the system',
            // Referenced by EnsureWardenOrUwaOfficial/EnsureAdmin/UserController's
            // GAMEPARK_INVITABLE_ROLES but never previously seeded - without these rows,
            // Role::where('role_name', ...) lookups return null and role attachment
            // silently no-ops (see BridgeFixturesSeeder's warden fixture, which needs
            // 'Park Warden' to exist for its role attachment to actually do anything).
            'Park Warden' => 'Manages a single park\'s gamepark portal',
            'Gamepark Officer' => 'Park-scoped portal staff account',
            'Community Wildlife Officer' => 'Community-facing park field staff',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['role_name' => $name], ['description' => $description]);
        }

        // firestore_id values must match android-native-backend-branch/scripts/fixtures/
        // shared-seed-fixtures.json's parks[].firestore_id exactly - this is what lets a
        // Firestore park doc and this MySQL row be recognized as "the same" record (see
        // BRIDGE-CONTRACT.md's "Seed fixture ID mapping" section).
        $parks = [
            ['park_name' => 'Bwindi Impenetrable National Park', 'district' => 'Kanungu', 'description' => 'Mountain gorilla habitat', 'firestore_id' => 'bwindi-impenetrable'],
            ['park_name' => 'Mgahinga Gorilla National Park', 'district' => 'Kisoro', 'description' => 'Gorilla and golden monkey habitat', 'firestore_id' => 'mgahinga-gorilla'],
            ['park_name' => 'Queen Elizabeth National Park', 'district' => 'Kasese', 'description' => 'Savannah wildlife park', 'firestore_id' => 'queen-elizabeth'],
            ['park_name' => 'Murchison Falls National Park', 'district' => 'Masindi', 'description' => 'Largest national park in Uganda', 'firestore_id' => 'murchison-falls'],
            ['park_name' => 'Kibale National Park', 'district' => 'Kabarole', 'description' => 'Primate capital of the world', 'firestore_id' => 'kibale'],
            ['park_name' => 'Semuliki National Park', 'district' => 'Bundibugyo', 'description' => 'Lowland tropical rainforest', 'firestore_id' => 'semuliki'],
            ['park_name' => 'Rwenzori Mountains National Park', 'district' => 'Kasese', 'description' => 'Glacial mountain range', 'firestore_id' => 'rwenzori-mountains'],
            ['park_name' => 'Lake Mburo National Park', 'district' => 'Kiruhura', 'description' => 'Savannah and lake wildlife park', 'firestore_id' => 'lake-mburo'],
            ['park_name' => 'Kidepo Valley National Park', 'district' => 'Kaabong', 'description' => 'Remote semi-arid savannah park', 'firestore_id' => 'kidepo-valley'],
            ['park_name' => 'Mount Elgon National Park', 'district' => 'Mbale', 'description' => 'Extinct volcano and caves', 'firestore_id' => 'mount-elgon'],
        ];

        foreach ($parks as $park) {
            // The canonical park/species registry is sourced from UWA records, so these
            // rows are explicitly marked official and verified at seed time (see the
            // confidence/last_verified_at migration). Anything not explicitly written as
            // official keeps the conservative default of "inferred".
            $park += ['confidence' => 'official', 'last_verified_at' => now()];
            Park::updateOrCreate(['park_name' => $park['park_name']], $park);
        }

        // Bridge-test accounts (ranger@wildwatch.app etc.) linked to their Firebase UID and
        // resolved park - see BridgeFixturesSeeder and BRIDGE-CONTRACT.md's "Seed fixture ID
        // mapping". Needs the roles and parks above to already exist.
        $this->call(BridgeFixturesSeeder::class);

        // Platform account that anonymous/guest mobile reporters are attributed to. Named
        // users match to their own portal row by firebase_uid; anonymous reporters have no
        // portal account, and FirestoreSyncMapper::anonymousReporterId() resolves them here
        // instead of (historically) silently attributing to the lowest user_id. Created
        // eagerly so the FK target always exists before incidents are seeded, and lazily
        // re-created by the mapper as a belt-and-braces guarantee in live environments.
        User::firstOrCreate(
            ['email' => 'anonymous@wildwatch.app'],
            [
                'first_name' => 'Anonymous',
                'last_name' => 'Reporter',
                'password_hash' => Hash::make(Str::random(40)),
                'account_status' => 'Active',
                'email_verified' => true,
            ]
        );

        $species = [
            ['common_name' => 'Elephant', 'scientific_name' => 'Loxodonta africana', 'conservation_status' => 'Vulnerable'],
            ['common_name' => 'Buffalo', 'scientific_name' => 'Syncerus caffer', 'conservation_status' => 'Least Concern'],
            ['common_name' => 'Lion', 'scientific_name' => 'Panthera leo', 'conservation_status' => 'Vulnerable'],
            ['common_name' => 'Mountain Gorilla', 'scientific_name' => 'Gorilla beringei beringei', 'conservation_status' => 'Endangered'],
        ];

        foreach ($species as $s) {
            $s += ['confidence' => 'official', 'last_verified_at' => now()];
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
        $this->seedNotifications();
        $this->seedNewsArticles($official);

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
        // Demo/QA dataset is deliberately scoped to a single game park (Bwindi Impenetrable)
        // so cross-system manual testing has one predictable set of incident data - parks stay
        // in the registry but only Bwindi carries seeded incidents. Was previously 5-10
        // incidents per park across all 10 parks; the user requested Bwindi-only.
        $park = Park::where('firestore_id', 'bwindi-impenetrable')->firstOrFail();

        // Keep the exact correlated Firestore doc contract (seed-{park.firestore_id}-incident-1
        // with source_system 'firestore', status 'New') - the portal and Firebase bridge rely
        // on it for cross-system manual testing (see BridgeFixturesSeederTest and
        // BRIDGE-CONTRACT.md's "Seed fixture ID mapping").
        $incidentTypes = ['Wildlife Sighting', 'Crop Damage', 'Livestock Loss', 'Property Damage', 'Human Injury', 'SOS'];

        // 'Escalated' is a boolean (is_escalated) since 2026-08-11; the status enum no
        // longer contains it (see add_is_escalated_to_incidents_table migration).
        $statuses = ['New', 'Assigned', 'In Progress', 'Resolved'];

        $severities = ['low', 'medium', 'high', 'light'];

        $locations = [
            ['sub_county' => 'Kayonza', 'parish' => 'Buhoma', 'village' => 'Buhoma Village'],
            ['sub_county' => 'Kanyantorogo', 'parish' => 'Nkuringo', 'village' => 'Nkuringo'],
            ['sub_county' => 'Butogota', 'parish' => 'Rubona', 'village' => 'Rubona'],
        ];

        $descriptions = [
            'Elephants raided a banana plantation overnight.',
            'Buffalo herd spotted near community farmland.',
            'Livestock killed by predators near park boundary.',
            'Crop damage reported by local farmer.',
            'Wildlife sighting reported by community member.',
            'Gorilla family crossed into community farmland — safari rangers responded.',
            'Human injury reported after wildlife encounter.',
            'Repeated crop raids in the last week.',
            'Community reported loud animal activity at night.',
            'Farmer lost several goats to wildlife.',
        ];

        [$baseLat, $baseLng] = [-1.05, 29.70];

        // Warm shade over the demo set so incidents don't all share the same instant and make
        // the portal's recent-incidents table look flat.
        $start = now()->subDays(6);

        for ($i = 0; $i < 8; $i++) {
            $location = $locations[$i % count($locations)];

            $isBridgeCorrelated = $i === 0;

            // Incidents are written through the query builder, not the model: firing the
            // IncidentObserver on every created would notify portal staff and echo to the
            // Firestore emulator per-row, which both inflates the notification count past the
            // "at most 5" target and makes seeding depend on the emulator being up.
            DB::table('incidents')->insert([
                'reported_by' => $official->user_id,
                'park_id' => $park->park_id,
                'incident_type' => $incidentTypes[$i % count($incidentTypes)],
                'severity' => $severities[$i % count($severities)],
                'description' => $descriptions[$i % count($descriptions)],
                'latitude' => $baseLat + ((($i + 1) % 5) * 0.012) - 0.03,
                'longitude' => $baseLng + ((($i + 2) % 5) * 0.011) - 0.03,
                'village' => $location['village'],
                'district' => $park->district,
                'sub_county' => $location['sub_county'],
                'parish' => $location['parish'],
                'status' => $statuses[$i % count($statuses)],
                'is_escalated' => $i % 5 === 4,
                'firestore_doc_id' => $isBridgeCorrelated ? "seed-{$park->firestore_id}-incident-1" : null,
                'source_system' => $isBridgeCorrelated ? 'firestore' : 'laravel',
                'created_at' => $start->copy()->addHours($i * 13),
            ]);
        }
    }

    private function seedNotifications(): void
    {
        // At most 5 sample notifications for the portal bell, addressed to the park warden
        // (portal-eligible Park Warden account from BridgeFixturesSeeder).
        $warden = User::where('email', 'warden@wildwatch.app')->first();
        if ($warden === null) {
            return; // bridge fixtures didn't run - nothing sensible to notify
        }

        $now = now();

        $rows = [
            ['SOS', 'Urgent: Elephant encounter near Buhoma', 'A lone elephant was spotted approaching Buhoma Village at 06:10. Advise keeping clear of the park boundary until rangers resolve.', $now->copy()->subHours(2)],
            ['Incident', 'Crop damage reported at Rubona', 'A farmer on the Rubona boundary reported pre-dawn crop damage. A ranger team is being dispatched to assess.', $now->copy()->subHours(9)],
            ['Assignment', 'Ranger assigned to sighting N-14', 'Ranger 2 was assigned to the gorilla sighting reported near Nkuringo. Expected to file a report within 24h.', $now->copy()->subHours(16)],
            ['Compensation', 'Claim CLM-1027 approved', 'UGX 750,000 compensation approved for livestock loss at Kayonza. Payment will be processed this week.', $now->copy()->subDays(1)->subHours(3)],
            ['General', 'Weekly park briefing available', 'The weekly Bwindi park briefing (gorilla counts, boundary patrols, community engagements) is now available in the portal.', $now->copy()->subDays(2)->subHours(5)],
        ];

        foreach ($rows as $offset => [$type, $title, $message, $createdAt]) {
            DB::table('notifications')->insert([
                'user_id' => $warden->user_id,
                'title' => $title,
                'message' => $message,
                'notification_type' => $type,
                'is_read' => $offset !== 0,
                'created_at' => $createdAt,
            ]);
        }
    }

    private function seedNewsArticles(User $official): void
    {
        // At most 5 news-feed articles, authored by the UWA official and all tied to the
        // single seeded park (Bwindi). The NewsArticleObserver normally mirrors published
        // articles to Firestore's /feed collection on create - suppressed here so seeding
        // doesn't depend on the Firestore emulator being up, which would otherwise throw
        // when the bridge isn't running. The portal reads its feed from Postgres directly.
        $park = Park::where('firestore_id', 'bwindi-impenetrable')->first();

        $articles = [
            [
                'title' => 'Ranger patrols intensify along Bwindi’s eastern boundary',
                'excerpt' => 'UWA has deployed additional boundary patrols after a spike in crop-raiding reports around Kayonza.',
                'body' => 'Following several nights of elephant activity along the forest edge, Uganda Wildlife Authority rangers have stepped up patrols on the eastern boundary of Bwindi Impenetrable National Park. Community liaison officers are also holding meetings in Kayonza and Rubona to coordinate response times. Early signs indicate the additional presence is reducing overnight incursions.',
                'category' => 'Boundary & Community',
                'theme' => 'SECURITY',
                'published_at' => now()->subDays(2)->subHours(4),
                'read_time' => '3 min',
            ],
            [
                'title' => 'New gorilla family opens for habituation in Rushaga sector',
                'excerpt' => 'A previously unhabituated gorilla group has been prepared for visitor tracking, expanding trekking capacity.',
                'body' => 'Another gorilla group in the Rushaga sector has completed habituation and will open for visitor tracking next month. The addition increases available trekking permits and spreads tourist pressure across more groups. Trackers describe the family as calm and well-accustomed to researchers.',
                'category' => 'Wildlife',
                'theme' => 'WILDLIFE',
                'published_at' => now()->subDays(4)->subHours(2),
                'read_time' => '4 min',
            ],
            [
                'title' => 'Community tree planting restores buffer around Nkuringo',
                'excerpt' => 'Local households joined UWA to plant 2,000 seedlings along the Nkuringo boundary.',
                'body' => 'Over two days, more than 300 community members planted 2,000 indigenous seedlings along the Nkuringo forest boundary. The exercise is part of an ongoing buffer restoration programme that reduces erosion and gives farmers shade crops near the park edge.',
                'category' => 'Conservation',
                'theme' => 'FOREST',
                'published_at' => now()->subDays(6)->subHours(1),
                'read_time' => '2 min',
            ],
            [
                'title' => 'Park officials confirm gorilla group sightings during Rushaga trek',
                'excerpt' => 'Trackers confirm consistent sightings of the Rushegura family as dry-season viewing begins.',
                'body' => 'With the dry season underway, habituated gorilla groups in the Rushaga sector continue to be sighted consistently. Trackers recorded a full morning with the Rushegura family, giving trekkers extended viewing time. Officials remind visitors to book permits in advance as slots fill quickly.',
                'category' => 'Tourism',
                'theme' => 'SUNSET',
                'published_at' => now()->subDays(8)->subHours(6),
                'read_time' => '3 min',
            ],
            [
                'title' => 'Weather outlook: clear skies expected for the coming weekend',
                'excerpt' => 'Meteorological forecasts point to a clear, dry weekend across Kanungu, ideal for gorilla trekking.',
                'body' => 'The weekend outlook for Kanungu district shows generally clear skies with low rain probability, making conditions ideal for gorilla trekking and boundary patrols. Visitors are advised to still carry rain gear as mountain conditions can change quickly. Trackers will continue monitoring the forest paths.',
                'category' => 'Weather',
                'theme' => 'SKY',
                'published_at' => now()->subDays(10)->subHours(2),
                'read_time' => '2 min',
            ],
        ];

        DB::table('news_articles')->insert(
            collect($articles)->map(function (array $a) use ($official, $park) {
                return [
                    'author_id' => $official->user_id,
                    'park_id' => $park?->park_id,
                    'title' => $a['title'],
                    'excerpt' => $a['excerpt'],
                    'body' => $a['body'],
                    'category' => $a['category'],
                    'source' => 'Uganda Wildlife Authority',
                    'read_time' => $a['read_time'],
                    'theme' => $a['theme'],
                    'published' => true,
                    'published_at' => $a['published_at'],
                ];
            })->all()
        );
    }
}
