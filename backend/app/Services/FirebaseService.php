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
     * Provision a Firebase Auth account (+ Firestore shadow doc) for a portal-invited
     * user who also needs to sign in to the mobile app. No password is set - the mobile
     * app is passwordless (Firebase email-link sign-in only), and Firebase matches that
     * later sign-in to this same account by email, inheriting the custom claims set here.
     *
     * Known caveat (currently inert, not a live bug): if Cloud Functions are ever
     * re-enabled (this project is on the Spark plan, which cannot run them at all - see
     * BRIDGE-CONTRACT.md), `onUserCreated` in functions/src/index.ts unconditionally
     * resets any newly-created Firebase user to role "public" and overwrites the
     * Firestore users/{uid} doc. That trigger would need a guard (e.g. skip when the doc
     * already has source_system: "laravel") before this method's claims could be trusted
     * to survive under Blaze.
     */
    public function provisionMobileAccount(string $email, string $displayName, string $role, ?string $parkFirestoreId)
    {
        // 1. Create User in Firebase Auth
        $userRecord = $this->auth->createUser([
            'email' => $email,
            'displayName' => $displayName,
        ]);

        $uid = $userRecord->uid;

        // 2. Set Custom Claims
        $this->auth->setCustomUserClaims($uid, [
            'role' => $role,
            'park_id' => $parkFirestoreId,
        ]);

        // 3. Create Shadow Document in Firestore
        $this->firestore->database()->collection('users')->document($uid)->set([
            'uid' => $uid,
            'email' => $email,
            'displayName' => $displayName,
            'role' => $role,
            'park_id' => $parkFirestoreId,
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
