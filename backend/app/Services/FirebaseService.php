<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;

class FirebaseService
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $factory = new Factory();

        // If we have a service account file, use it.
        // Otherwise, the SDK will look for GOOGLE_APPLICATION_CREDENTIALS
        // or fail gracefully if we're only using emulators.
        $serviceAccount = storage_path('app/firebase-auth.json');
        if (file_exists($serviceAccount)) {
            $factory = $factory->withServiceAccount($serviceAccount);
        }

        // Discovery for emulators via environment variables is handled
        // automatically by the SDK if they are set (which we did in .env.laravel.local).
        // However, we can be explicit for safety in local dev.
        if (config('app.env') === 'local') {
            if ($authHost = env('FIREBASE_AUTH_EMULATOR_HOST')) {
                $factory = $factory->withAuthEmulator('http://' . $authHost);
            }
            if ($firestoreHost = env('FIRESTORE_EMULATOR_HOST')) {
                $factory = $factory->withFirestoreEmulator(...explode(':', $firestoreHost));
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
}
