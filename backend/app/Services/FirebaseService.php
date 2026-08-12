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
            // Use a dummy service account to satisfy the SDK's internal checks while it
            // actually connects to the emulators (FIREBASE_AUTH_EMULATOR_HOST/
            // FIRESTORE_EMULATOR_HOST - see Kreait\Firebase\Util::authEmulatorHost()). The
            // private key below must still be a structurally valid PEM (a fresh, unused,
            // 2048-bit RSA key - never used to actually authenticate anywhere) because the
            // SDK validates it eagerly when building its API client, before any request
            // reaches the emulator. A previous version of this key was truncated/invalid,
            // which made every real (unmocked) call to $this->auth - e.g. createUser() -
            // fail with "OpenSSL unable to validate key" before ever reaching the emulator;
            // omitting the service account entirely instead fails even earlier with "Unable
            // to create an API client without credentials". Both confirmed live against the
            // Auth+Firestore emulators while testing the ranger-invite flow.
            $factory = $factory->withServiceAccount([
                'type' => 'service_account',
                'project_id' => env('GCLOUD_PROJECT', 'wildwatch-82abc'),
                'private_key_id' => 'dummy',
                'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCbhnKLM5vsgmMz\n3dvb7Hc9X5exGpI6BzqWf8bIVDLZ0Xtc0c4238Yd1K7+Db2xACAI3wYmvTpNEM9a\nLSmhjMwQ1380TUiDvP1hi91ulVbnbgUCtUVFEm+tR50ZhoOX5BiG1VoavRsfbBdr\nhiMzY8FuGZqUSGGCND2tdH7MQyl0/yRGgNlEOaGQ1rG+jjTCEsg0r1O0PhhXFqkG\n83yoQ3dptgJS/NFtV1uZM84TY+yJGjRTEyoVbtxHkrzgo4E2CuHUrWlm1nhN+Yjh\nDKz4AU7KxHTU7nKXC0EZVGQVzLYmvp1EreIhi3VleeH+Ias+IcNzxPZochCGdokT\nGO1Ze6s/AgMBAAECggEAAgG3CFZJQbFkofwdXO5CbHSimpOzCkdc7ry733r+o+Hg\nVxfsWYGTC9V9RGpVhMKQiDhoOACmzWkHmbQsv23/QBAEXJy7g6w+IknNrRqoXj9p\nfyN6mETdBceO7C7/cHZewoYhTNyocyUCWis1mrNDvyYFXTatoPui6PE4QMObgYsV\nKid0+iss+yePK7uUO5WUZ22tArtVljhLichhDz5n3gyumQSB0VA2if+uZ9DFb79l\n+/88rtwGAOfpNY5LO01VYliwMM4LboeY47JjVxzStbqpPIAZPf+1Q2zVhS5ZOKgH\ntEFrWYjB95A3Ey2X3i8QeeLR7uSgsohGFjq8pg3BYQKBgQDQSAxthFon/60+HTIi\nc7Oo4COSWI3ijA4Jwm/lqVuHJtt8g2FMPUD9bHVEpsa3RwceTIfs8Kj2sPzpfrpN\nVkSoErLp1pMB0frNUNvH36BpZT8mbtdniQP/wr+ZGaIH78LMAwp8gF8MPUdKpAP+\n0Yrs6aWHJcpX04MyYUda3UOn0QKBgQC/KC65G5gQrDL+usYfwGpDXPrrz6vkxmZe\n8+sHQwdcygWZtWJQuJ1OituRwCnAJFPTTYB5RKsH8EvGcYUBJ3s7Rs4W7odCpIH8\nhIw6oPBW1LBXucilmQUjo24A5Vz89B72wXfGYh/3RAgIVG/QCBvV1shdMSDQSGKW\na7NAnaL2DwKBgDUxAUOC1od6i2Lej+wugkZxn4QDa5Dc1cT2TB9p5f8ZFFqzLskK\np6tQ5I34za0GzbGWN+xx9aSyxJRZEfkoO/Z0eA6yBu8jEhsXOFnOKahg/ASzr/04\nB7ZspQPTgQbn22bArA/ptNxqVeehBYgxOXqRnP1r0EYntUzLfS6ebWXRAoGAbLkT\nEg+ixuDaRE3BADA1gEjzIopEj2NUuG7tX3z9RAZXdxxWZekK97A8wEJWvMUstEMh\nblfjGynOP3kzl/t3uLhF4X8biYj9sb1F8Na2u/xOrCar+5vz81gx6eqKoAjNT7Ws\nRTZsTfvwwaQc0Gq8QjzeSzr1GeIByOJK2taN6HsCgYB7w65m3ZlJ1u9AxFujmrM+\nSR/Fm8VtJDYM+uVwVCj3aBbDVmJlYM0vVTme+idvcoGcHNOgNWrqbgUXQDgQlLT0\nlVNZpR+MHIjD+OUmyMvpz31zl+7Xh1KX533Tevufh2Gmr2kHRqUEMkcRGMJlS5M3\neIddZpngr+GXbZXc1hmvvg==\n-----END PRIVATE KEY-----\n",
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
