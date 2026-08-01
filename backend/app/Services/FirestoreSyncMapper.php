<?php

namespace App\Services;

use App\Models\Park;
use App\Models\Species;
use App\Models\User;
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
            'description' => $this->firstNonEmpty([
                $data['summary'] ?? null,
                $data['description'] ?? null,
            ], 'No description provided'),
            'latitude' => (float) ($data['lat'] ?? $data['latitude'] ?? 0),
            'longitude' => (float) ($data['lng'] ?? $data['longitude'] ?? 0),
            'village' => $this->firstNonEmpty([
                $data['community'] ?? null,
                $data['locationName'] ?? null,
                $data['village'] ?? null,
            ]),
            'status' => $this->mapIncidentStatus($data['status'] ?? null),
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
            'latitude' => (float) ($data['lat'] ?? $data['latitude'] ?? 0),
            'longitude' => (float) ($data['lng'] ?? $data['longitude'] ?? 0),
            'status' => $this->mapSosStatus($data['status'] ?? null),
            'resolved_at' => $data['resolved_at'] ?? null,
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

        if (isset($data['reported_by']) && is_numeric($data['reported_by'])) {
            return (int) $data['reported_by'];
        }

        $fallback = User::query()->orderBy('user_id')->value('user_id');

        return (int) ($fallback ?? 1);
    }

    private function resolveParkId(mixed $park): int
    {
        if (is_numeric($park)) {
            return (int) $park;
        }

        if (! is_string($park) || $park === '') {
            return (int) (Park::query()->orderBy('park_id')->value('park_id') ?? 1);
        }

        $normalized = Str::of($park)
            ->replace('_', ' ')
            ->lower()
            ->squish()
            ->value();

        $match = Park::query()
            ->get(['park_id', 'park_name'])
            ->first(function (Park $candidate) use ($normalized) {
                $candidateName = Str::of($candidate->park_name)->lower()->squish()->value();

                return $candidateName === $normalized
                    || str_contains($candidateName, $normalized)
                    || str_contains($normalized, $candidateName);
            });

        return (int) ($match?->park_id ?? Park::query()->orderBy('park_id')->value('park_id') ?? 1);
    }

    private function resolveSpeciesId(array $data): int
    {
        if (isset($data['species_id']) && is_numeric($data['species_id'])) {
            return (int) $data['species_id'];
        }

        $speciesName = $data['species'] ?? $data['species_name'] ?? null;
        if (is_string($speciesName) && $speciesName !== '') {
            $speciesId = Species::where('common_name', $speciesName)->value('species_id');
            if ($speciesId) {
                return (int) $speciesId;
            }
        }

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

    private function mapIncidentStatus(mixed $status): string
    {
        return match (Str::lower((string) ($status ?? 'open'))) {
            'assigned' => 'Assigned',
            'in_progress', 'in progress' => 'In Progress',
            'resolved' => 'Resolved',
            'escalated' => 'Escalated',
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
