<?php

namespace App\Services;

use App\Models\Park;
use App\Models\Species;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirestoreSyncMapper
{
    public function mapIncidentAttributes(string $docId, array $data): array
    {
        return [
            'firestore_doc_id' => $docId,
            'source_system' => 'firestore',
            'reported_by' => $this->resolveUserId($data),
            'park_id' => $this->resolveParkId($data['park'] ?? $data['park_id'] ?? null),
            'incident_type' => $this->mapIncidentType($data['type'] ?? null),
            'severity' => $this->mapSeverity($data['severity'] ?? null),
            'description' => $this->firstNonEmpty([
                $data['summary'] ?? null,
                $data['description'] ?? null,
            ], 'No description provided'),
            'latitude' => $this->nullableCoordinate($data['lat'] ?? $data['latitude'] ?? null, 90),
            'longitude' => $this->nullableCoordinate($data['lng'] ?? $data['longitude'] ?? null, 180),
            'village' => $this->firstNonEmpty([
                $data['community'] ?? null,
                $data['locationName'] ?? null,
                $data['village'] ?? null,
            ]),
            'district' => $this->firstNonEmpty([
                $data['district'] ?? null,
            ]),
            'sub_county' => $this->firstNonEmpty([
                $data['sub_county'] ?? null,
                $data['subCounty'] ?? null,
            ]),
            'parish' => $this->firstNonEmpty([
                $data['parish'] ?? null,
            ]),
            'status' => $this->mapIncidentStatus($data['status'] ?? null),
            'is_escalated' => (bool) ($data['isEscalated'] ?? false),
        ];
    }

    public function mapSightingAttributes(string $docId, array $data): array
    {
        return [
            'firestore_doc_id' => $docId,
            'source_system' => 'firestore',
            'ranger_id' => $this->resolveUserId($data, 'ranger_uid'),
            'species_id' => $this->resolveSpeciesId($data),
            'park_id' => $this->resolveParkId($data['park'] ?? $data['park_id'] ?? null),
            'latitude' => (float) ($data['lat'] ?? $data['latitude'] ?? 0),
            'longitude' => (float) ($data['lng'] ?? $data['longitude'] ?? 0),
            'number_seen' => (int) ($data['number_seen'] ?? $data['count'] ?? 1),
            'notes' => $this->firstNonEmpty([
                $data['notes'] ?? null,
                $data['description'] ?? null,
            ]),
            'approval_status' => $this->mapApprovalStatus($data),
            'sighting_time' => $data['sighting_time'] ?? $data['reportedAt'] ?? now(),
            'latitude' => $this->nullableCoordinate($data['lat'] ?? $data['latitude'] ?? null, 90),
            'longitude' => $this->nullableCoordinate($data['lng'] ?? $data['longitude'] ?? null, 180),
        ];
    }

    public function mapSosAlertAttributes(string $docId, array $data): array
    {
        return [
            'firestore_doc_id' => $docId,
            'source_system' => 'firestore',
            'reported_by' => $this->resolveUserId($data),
            'park_id' => $this->resolveParkId($data['park'] ?? $data['park_id'] ?? null),
            'emergency_type' => $this->mapEmergencyType($data['emergency_type'] ?? $data['type'] ?? null),
            'description' => $this->firstNonEmpty([
                $data['description'] ?? null,
                $data['summary'] ?? null,
            ]),
            'status' => $this->mapSosStatus($data['status'] ?? null),
            'resolved_at' => $data['resolved_at'] ?? null,
            'latitude' => $this->nullableCoordinate($data['lat'] ?? $data['latitude'] ?? null, 90),
            'longitude' => $this->nullableCoordinate($data['lng'] ?? $data['longitude'] ?? null, 180),
        ];
    }

    public function incidentToFirestore(array $overrides = []): array
    {
        return array_merge(['source_system' => 'laravel'], $overrides);
    }

    private function resolveUserId(array $data, string $primaryKey = 'userId'): int
    {
        $firebaseUid = $data[$primaryKey]
            ?? $data['userId']
            ?? $data['reporter_uid']
            ?? $data['uid']
            ?? null;

        if (is_string($firebaseUid) && $firebaseUid !== '') {
            $userId = User::where('firebase_uid', $firebaseUid)->value('user_id');
            if ($userId) {
                return (int) $userId;
            }
        }

        // Identity spoof fix: a client-supplied numeric reported_by is never trusted.
        // The reverse-key here is `userId`, which VerifyFirebaseIdToken -> injectVerifiedIdentity
        // has already overwritten with the verified Firebase UID, so by this point the only
        // way $firebaseUid is a string is if it came from Firebase itself. Anonymous/guest
        // reporters (no portal account) attribute to a dedicated platform account instead of
        // silently acting as whichever real user happens to have the lowest user_id.
        return $this->anonymousReporterId();
    }

    /**
     * The platform account anonymous/guest reporters (and unmatched Firebase UIDs) are
     * attributed to. Used as the FK target for reported_by/ranger_id on mobile-direct
     * ingests so incidents never ride on a real user's row by accident.
     */
    private function anonymousReporterId(): int
    {
        $anonymous = User::where('email', 'anonymous@wildwatch.app')->value('user_id');
        if ($anonymous) {
            return (int) $anonymous;
        }

        try {
            $anonymous = User::create([
                'first_name' => 'Anonymous',
                'last_name' => 'Reporter',
                'email' => 'anonymous@wildwatch.app',
                'password_hash' => Hash::make(Str::random(40)),
                'account_status' => 'Active',
                'email_verified' => true,
            ]);

            return (int) $anonymous->user_id;
        } catch (\Throwable $e) {
            // Only this extreme: user creation itself failed. Log it loudly rather than
            // silently attributing to the wrong person again - the webhook still needs a
            // FK target, but the identity bug must never be invisible twice.
            Log::error('FirestoreSyncMapper: could not create anonymous reporter, falling back to first user.', ['error' => $e->getMessage()]);

            return (int) (User::query()->orderBy('user_id')->value('user_id') ?? 1);
        }
    }

    private function resolveParkId(mixed $park): int
    {
        if (is_numeric($park)) {
            $parkId = Park::whereKey((int) $park)->value('park_id');

            return (int) ($parkId ?? $this->fallbackParkId('numeric park_id '.$park.' did not resolve'));
        }

        if (! is_string($park) || $park === '') {
            return $this->fallbackParkId('empty/missing park name');
        }

        // 1. Exact firestore_id match first (seeded: e.g. bwindi-impenetrable). A real FK
        //    slug is unambiguous, which is why it beats substring name matching - the
        //    previous code never consulted firestore_id at all (see BRIDGE-CONTRACT.md).
        $firestoreId = Str::of($park)->replace('_', '-')->lower()->toString();
        $parkId = Park::where('firestore_id', $firestoreId)->value('park_id');
        if ($parkId) {
            return (int) $parkId;
        }

        // 2. Normalized name matching (Kotlin enum name e.g. BWINDI_IMPENETRABLE). Substring
        //    matching both directions is kept, but only after exact comparison fails.
        $normalized = Str::of($park)
            ->replace(['_', '-'], ' ')
            ->lower()
            ->squish()
            ->toString();

        $match = Park::query()
            ->get(['park_id', 'park_name', 'firestore_id'])
            ->first(function (Park $candidate) use ($normalized) {
                $candidateName = Str::of($candidate->park_name)->lower()->squish()->toString();
                $firestoreId = Str::of((string) $candidate->firestore_id)->replace('-', ' ')->toString();

                return $candidateName === $normalized
                    || $firestoreId === $normalized
                    || str_contains($candidateName, $normalized)
                    || str_contains($normalized, $candidateName);
            });

        if ($match) {
            return (int) $match->park_id;
        }

        return $this->fallbackParkId("park name '{$park}' resolved to no known park");
    }

    private function fallbackParkId(string $reason): int
    {
        Log::warning('FirestoreSyncMapper: unresolved park, attributing to first park.', ['reason' => $reason]);

        return (int) (Park::query()->orderBy('park_id')->value('park_id') ?? 1);
    }

    private function resolveSpeciesId(array $data): int
    {
        $speciesId = null;

        if (isset($data['species_id']) && is_numeric($data['species_id'])) {
            $speciesId = Species::whereKey((int) $data['species_id'])->value('species_id');
        }

        $speciesName = $data['species'] ?? $data['species_name'] ?? null;
        if (! $speciesId && is_string($speciesName) && $speciesName !== '') {
            $normalized = Str::lower(trim($speciesName));

            $speciesId = Species::query()
                ->get(['species_id', 'common_name'])
                ->first(fn (Species $s) => Str::lower(trim($s->common_name)) === $normalized)
                ?->species_id;

            if (! $speciesId) {
                $speciesId = Species::query()
                    ->get(['species_id', 'common_name'])
                    ->first(fn (Species $s) => str_contains(Str::lower($s->common_name), $normalized))
                    ?->species_id;
            }
        }

        if ($speciesId) {
            return (int) $speciesId;
        }

        Log::warning('FirestoreSyncMapper: unresolved species, attributing to first species.', ['species' => $speciesName ?? '(none)']);

        return (int) (Species::query()->orderBy('species_id')->value('species_id') ?? 1);
    }

    private function mapIncidentType(mixed $type): string
    {
        return match (Str::lower((string) ($type ?? 'conflict'))) {
            'sighting', 'wildlife sighting' => 'Wildlife Sighting',
            'emergency', 'human injury' => 'Human Injury',
            'poaching' => 'Property Damage',
            'crop damage' => 'Crop Damage',
            'livestock loss' => 'Livestock Loss',
            'human fatality' => 'Human Fatality',
            default => 'Property Damage',
        };
    }

    /**
     * Persist the mobile-reported severity so the portal can triage by it (previously
     * it was silently dropped - see BRIDGE-CONTRACT.md's known-gaps list). Stored in the
     * same lowercase vocabulary the Kotlin IncidentSeverity enum defines.
     */
    private function mapSeverity(mixed $severity): string
    {
        return match (Str::lower((string) ($severity ?? 'medium'))) {
            'low' => 'low',
            'light' => 'light',
            'high' => 'high',
            'critical' => 'high',
            default => 'medium',
        };
    }

    /**
     * Coordinates are stored as NULL rather than fabricated (0,0) values - the old
     * behaviour seeded phantom "null island" incidents at the Gulf of Guinea that the
     * ranger map then silently hid (see ReportIncidentViewModel). Values outside the
     * real world lat/lng ranges are also rejected.
     */
    private function nullableCoordinate(mixed $value, float $maxAbs): ?float
    {
        if (is_numeric($value)) {
            $coordinate = (float) $value;

            if (abs($coordinate) > 0.0000001 && abs($coordinate) <= $maxAbs) {
                return $coordinate;
            }
        }

        return null;
    }

    private function mapIncidentStatus(mixed $status): string
    {
        return match (Str::lower((string) ($status ?? 'open'))) {
            'assigned' => 'Assigned',
            // Legacy documents may still carry the old folded-in 'escalated'
            // status; is_escalated (see mapIncidentAttributes) is the source of
            // truth for the flag now, so this just keeps the status column sane.
            'in_progress', 'in progress', 'escalated' => 'In Progress',
            'resolved' => 'Resolved',
            default => 'New',
        };
    }

    private function mapApprovalStatus(array $data): ?string
    {
        $status = Str::lower((string) ($data['approval_status'] ?? $data['status'] ?? ''));

        if ($status === '') {
            return null;
        }

        return match ($status) {
            'approved', 'resolved' => 'Approved',
            'rejected' => 'Rejected',
            'pending', 'open', 'submitted' => 'Pending',
            default => Str::headline($status),
        };
    }

    private function mapEmergencyType(mixed $type): string
    {
        return match (Str::lower((string) ($type ?? 'other'))) {
            'wildlife attack' => 'Wildlife Attack',
            'dangerous sighting' => 'Dangerous Sighting',
            'human injury', 'emergency' => 'Human Injury',
            'property threat' => 'Property Threat',
            default => 'Other',
        };
    }

    private function mapSosStatus(mixed $status): string
    {
        return match (Str::lower((string) ($status ?? 'pending'))) {
            'responding', 'in_progress', 'in progress' => 'Responding',
            'resolved' => 'Resolved',
            default => 'Pending',
        };
    }

    private function firstNonEmpty(array $values, ?string $default = null): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $default;
    }
}
