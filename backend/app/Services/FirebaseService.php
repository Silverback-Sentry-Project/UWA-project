<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Firestore;

class FirebaseService
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $factory = new Factory();

        if (config('app.env') === 'local') {
            // Use a dummy service account to satisfy the SDK's internal checks
            // while it actually connects to the emulators.
            $factory = $factory->withServiceAccount([
                'type' => 'service_account',
                'project_id' => env('GCLOUD_PROJECT', 'demo-wildwatch-local'),
                'private_key_id' => 'dummy',
                'private_key' => "-----BEGIN PRIVATE KEY-----\nMIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAM7P\n-----END PRIVATE KEY-----\n",
                'client_email' => 'dummy@example.com',
                'client_id' => '123',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/dummy%40example.com'
            ]);
        } else {
            $serviceAccount = storage_path('app/firebase-auth.json');
            if (file_exists($serviceAccount)) {
                $factory = $factory->withServiceAccount($serviceAccount);
            }
        }

        $this->auth = $factory->createAuth();
        $this->firestore = $factory->createFirestore();
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function firestore(): Firestore
    {
        return $this->firestore;
    }

    /**
     * Create a Ranger account in Firebase Auth and a shadow document in Firestore.
     */
    public function createRangerAccount(string $email, string $password, string $displayName, string $parkId)
    {
        // 1. Create User in Firebase Auth
        $userRecord = $this->auth->createUser([
            'email' => $email,
            'password' => $password,
            'displayName' => $displayName,
        ]);

        $uid = $userRecord->uid;

        // 2. Set Custom Claims
        $this->auth->setCustomUserClaims($uid, [
            'role' => 'ranger',
            'park_id' => $parkId,
        ]);

        // 3. Create Shadow Document in Firestore
        $this->firestore->database()->collection('users')->document($uid)->set([
            'uid' => $uid,
            'email' => $email,
            'displayName' => $displayName,
            'role' => 'ranger',
            'park_id' => $parkId,
            'source_system' => 'laravel',
            'created_at' => new \DateTime(),
        ]);

        return $uid;
    }

    /**
     * Merge portal-originated fields into a Firestore incident document.
     *
     * @param  array<string, mixed>  $fields
     */
    public function syncIncidentDocument(string $firestoreDocId, array $fields): void
    {
        $payload = array_merge($fields, [
            'source_system' => 'laravel',
            'updated_at' => new \DateTime(),
        ]);

        $this->firestore->database()
            ->collection('incidents')
            ->document($firestoreDocId)
            ->set($payload, ['merge' => true]);
    }

    /**
     * Merge portal-originated fields into a Firestore sighting document.
     *
     * @param  array<string, mixed>  $fields
     */
    public function syncSightingDocument(string $firestoreDocId, array $fields): void
    {
        $payload = array_merge($fields, [
            'source_system' => 'laravel',
            'updated_at' => new \DateTime(),
        ]);

        $this->firestore->database()
            ->collection('sightings')
            ->document($firestoreDocId)
            ->set($payload, ['merge' => true]);
    }

    /**
     * Publish a news article to the Firestore community feed.
     *
     * @param  array<string, mixed>  $fields
     */
    public function syncFeedArticle(string $firestoreDocId, array $fields): void
    {
        $payload = array_merge($fields, [
            'source_system' => 'laravel',
            'updated_at' => new \DateTime(),
        ]);

        $this->firestore->database()
            ->collection('feed')
            ->document($firestoreDocId)
            ->set($payload, ['merge' => true]);
    }
}
