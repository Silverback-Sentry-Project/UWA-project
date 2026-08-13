<?php

namespace App\Services;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Firestore;

class FirebaseService
{
    private ?Factory $factory = null;

    private ?Auth $auth = null;

    private ?Firestore $firestore = null;

    /**
     * Built lazily (not in the constructor) so that a misconfigured/missing credential only
     * fails the request that actually needs Firebase, with an exception the caller can
     * attribute to this specific call - not an opaque failure during dependency injection,
     * before any of this class's own code has run. This was a real production bug: every
     * request touching a Firebase-dependent middleware/observer 500'd unconditionally,
     * because the old eager constructor threw during DI resolution, outside any call site's
     * own try/catch (see VerifyFirebaseIdToken, which could only ever catch exceptions from
     * its own verifyIdToken() call, never from building the client that call needed).
     */
    private function factory(): Factory
    {
        if ($this->factory !== null) {
            return $this->factory;
        }

        $factory = new Factory;

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
                'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/dummy%40example.com',
            ]);
        } else {
            // Render's filesystem is ephemeral and nothing in the Docker build writes a
            // credentials file into it, so the real service-account JSON has to arrive as an
            // env var, not a checked-in/uploaded file. FIREBASE_CREDENTIALS_JSON holds the
            // full downloaded JSON verbatim (set in the Render dashboard, sync: false in
            // render.yaml - never committed). The storage_path() fallback below still works
            // for anyone who genuinely does deploy with a mounted file.
            $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');

            if (is_string($credentialsJson) && $credentialsJson !== '') {
                $decoded = json_decode($credentialsJson, true);
                if (! is_array($decoded)) {
                    throw new \RuntimeException(
                        'FIREBASE_CREDENTIALS_JSON is set but is not valid JSON.'
                    );
                }
                $factory = $factory->withServiceAccount($decoded);
            } else {
                $serviceAccount = storage_path('app/firebase-auth.json');
                if (file_exists($serviceAccount)) {
                    $factory = $factory->withServiceAccount($serviceAccount);
                } else {
                    // This used to fail silently down inside the SDK ("Unable to determine
                    // the Firebase Project ID" / "Unable to create an API client without
                    // credentials") with no indication of what to actually set - confirmed
                    // live as the root cause of every /api/mobile/* call 500ing in
                    // production, since nothing here ever configured a project ID or
                    // credentials at all. Naming the missing env var directly turns a
                    // confusing 500 into an actionable message.
                    throw new \RuntimeException(
                        'Firebase credentials are not configured for this environment. '.
                        'Set FIREBASE_CREDENTIALS_JSON to the full service-account JSON '.
                        '(from Firebase Console → Project Settings → Service accounts) '.
                        'or place it at storage/app/firebase-auth.json.'
                    );
                }
            }
        }

        return $this->factory = $factory;
    }

    public function auth(): Auth
    {
        return $this->auth ??= $this->factory()->createAuth();
    }

    public function firestore(): Firestore
    {
        return $this->firestore ??= $this->factory()->createFirestore();
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
        $userRecord = $this->auth()->createUser([
            'email' => $email,
            'displayName' => $displayName,
        ]);

        $uid = $userRecord->uid;

        // Steps 2-3 are wrapped separately from step 1: if either fails, the just-created
        // Auth user is deleted before re-throwing. Previously a failure here left an
        // orphaned Firebase Auth account with this email and no matching Laravel row (the
        // DB transaction the caller wraps this in rolls back) - every retry then failed
        // createUser() with a duplicate-email error, forever, as an uncaught exception -
        // confirmed live as a real intermittent 500 on /users/invite.
        try {
            // 2. Set Custom Claims
            $this->auth()->setCustomUserClaims($uid, [
                'role' => $role,
                'park_id' => $parkFirestoreId,
            ]);

            // 3. Create Shadow Document in Firestore
            $this->firestore()->database()->collection('users')->document($uid)->set([
                'uid' => $uid,
                'email' => $email,
                'displayName' => $displayName,
                'role' => $role,
                'park_id' => $parkFirestoreId,
                'source_system' => 'laravel',
                'created_at' => new \DateTime,
            ]);
        } catch (\Throwable $e) {
            try {
                $this->auth()->deleteUser($uid);
            } catch (\Throwable) {
                // Best-effort cleanup - the original exception below is what matters.
            }

            throw $e;
        }

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
            'updated_at' => new \DateTime,
        ]);

        $this->firestore()->database()
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
            'updated_at' => new \DateTime,
        ]);

        $this->firestore()->database()
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
            'updated_at' => new \DateTime,
        ]);

        $this->firestore()->database()
            ->collection('feed')
            ->document($firestoreDocId)
            ->set($payload, ['merge' => true]);
    }

    /**
     * Remove a news article's Firestore mirror - used when a previously-published article is
     * unpublished or deleted, so the mobile feed doesn't keep serving it after the portal no
     * longer considers it live.
     */
    public function deleteFeedArticle(string $firestoreDocId): void
    {
        $this->firestore()->database()
            ->collection('feed')
            ->document($firestoreDocId)
            ->delete();
    }
}
