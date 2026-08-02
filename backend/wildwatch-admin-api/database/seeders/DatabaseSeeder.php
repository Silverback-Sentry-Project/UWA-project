<?php

namespace Database\Seeders;

use App\Models\EvidenceForm;
use App\Models\EvidenceFormSubmission;
use App\Models\Notification;
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
            'Gamepark Officer' => 'Logs in via the Gamepark portal for a single park: handles assignments, emergency notifications, and evidence forms',
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

        // One default Gamepark portal account per park.
        // Email/password below are starter credentials — each park's password can (and should) be changed after first login.
        $gameparkRole = Role::where('role_name', 'Gamepark Officer')->first();
        foreach (Park::all() as $index => $park) {
            $slug = str($park->park_name)->before(' National Park')->slug('')->lower();
            $gamepark = User::firstOrCreate(
                ['email' => "{$slug}.gamepark@uwa.go.ug"],
                [
                    'first_name' => $park->park_name,
                    'last_name' => 'Gamepark',
                    'password_hash' => Hash::make('Gamepark#' . ($index + 1) . '2026'),
                    'account_status' => 'Active',
                    'email_verified' => true,
                    'park_id' => $park->park_id,
                ]
            );

            if (! $gamepark->park_id) {
                $gamepark->update(['park_id' => $park->park_id]);
            }
            if (! $gamepark->roles->contains($gameparkRole->role_id)) {
                $gamepark->roles()->attach($gameparkRole->role_id);
            }
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

        // Demo evidence form + submissions for one park, so the Forms and
        // Submissions pages have real data to show before residents are wired up.
        $demoPark = Park::where('park_name', 'Queen Elizabeth National Park')->first();
        $demoGamepark = User::where('park_id', $demoPark->park_id)->first();

        $demoForm = EvidenceForm::firstOrCreate(
            ['park_id' => $demoPark->park_id, 'title' => 'Human-Wildlife Conflict Evidence Report'],
            [
                'created_by' => $demoGamepark->user_id,
                'description' => 'Used to capture evidence of crop damage or livestock loss for compensation review.',
                'status' => 'Published',
            ]
        );

        if ($demoForm->fields()->count() === 0) {
            $demoFields = [
                ['label' => 'Reporter full name', 'field_type' => 'text', 'is_required' => true, 'position' => 0],
                ['label' => 'Nature of incident', 'field_type' => 'select', 'options' => ['Crop damage', 'Livestock loss', 'Property damage', 'Other'], 'is_required' => true, 'position' => 1],
                ['label' => 'Date of incident', 'field_type' => 'date', 'is_required' => true, 'position' => 2],
                ['label' => 'Description', 'field_type' => 'textarea', 'is_required' => true, 'position' => 3],
                ['label' => 'Photo evidence', 'field_type' => 'image', 'is_required' => false, 'position' => 4],
            ];
            foreach ($demoFields as $f) {
                $demoForm->fields()->create($f);
            }
        }

        if ($demoForm->submissions()->count() === 0) {
            $submissionAwaitingReview = EvidenceFormSubmission::create([
                'form_id' => $demoForm->form_id,
                'park_id' => $demoPark->park_id,
                'submitted_by_name' => 'Grace Kyomuhendo',
                'submitted_by_contact' => '+256 700 000111',
                'status' => 'Submitted',
            ]);

            foreach ($demoForm->fields as $field) {
                $submissionAwaitingReview->answers()->create([
                    'field_id' => $field->field_id,
                    'value' => match ($field->field_type) {
                        'select' => 'Crop damage',
                        'date' => now()->subDays(2)->toDateString(),
                        'textarea' => 'Elephants trampled roughly half an acre of banana plantation overnight.',
                        default => 'Grace Kyomuhendo',
                    },
                ]);
            }

            Notification::create([
                'user_id' => $demoGamepark->user_id,
                'title' => 'New form submission',
                'message' => "A new \"{$demoForm->title}\" submission was received and is awaiting review.",
                'notification_type' => 'FormSubmission',
            ]);
        }
    }
}
