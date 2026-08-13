<?php

namespace Tests\Unit;

use App\Services\FirebaseService;
use Tests\TestCase;

class FirebaseServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('FIREBASE_CREDENTIALS_JSON');
        unset($_ENV['FIREBASE_CREDENTIALS_JSON'], $_SERVER['FIREBASE_CREDENTIALS_JSON']);

        parent::tearDown();
    }

    public function test_auth_throws_a_clear_error_when_nothing_is_configured_outside_local(): void
    {
        config(['app.env' => 'production']);
        putenv('FIREBASE_CREDENTIALS_JSON');

        // A real Render deployment never has this file - it's gitignored and nothing in the
        // Docker build writes it - but a stray leftover copy from local emulator testing can
        // exist on a dev machine (see FirebaseService's file-fallback branch). Move it aside
        // for the duration of this test so the assertion reflects the real production
        // scenario (truly nothing configured) rather than this machine's local state.
        $strayFile = storage_path('app/firebase-auth.json');
        $movedAside = null;
        if (file_exists($strayFile)) {
            $movedAside = $strayFile.'.moved-for-test';
            rename($strayFile, $movedAside);
        }

        try {
            $service = new FirebaseService;

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Firebase credentials are not configured');

            $service->auth();
        } finally {
            if ($movedAside !== null) {
                rename($movedAside, $strayFile);
            }
        }
    }

    public function test_auth_throws_a_clear_error_when_firebase_credentials_json_is_not_valid_json(): void
    {
        config(['app.env' => 'production']);
        putenv('FIREBASE_CREDENTIALS_JSON={not valid json');

        $service = new FirebaseService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not valid JSON');

        $service->auth();
    }

    public function test_auth_builds_successfully_from_firebase_credentials_json(): void
    {
        config(['app.env' => 'production']);
        putenv('FIREBASE_CREDENTIALS_JSON='.json_encode($this->dummyServiceAccount()));

        $service = new FirebaseService;

        // Building the client is enough to prove the env var was read and parsed correctly -
        // no network call happens until an actual API method is invoked.
        $this->assertNotNull($service->auth());
    }

    /**
     * @return array<string, string>
     */
    private function dummyServiceAccount(): array
    {
        // Structurally valid (parseable PEM), never used to authenticate anywhere - same
        // fixture shape as the local-dev dummy in FirebaseService, generated solely for this
        // test to prove FIREBASE_CREDENTIALS_JSON parsing works end to end.
        return [
            'type' => 'service_account',
            'project_id' => 'wildwatch-82abc',
            'private_key_id' => 'dummy',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCbhnKLM5vsgmMz\n3dvb7Hc9X5exGpI6BzqWf8bIVDLZ0Xtc0c4238Yd1K7+Db2xACAI3wYmvTpNEM9a\nLSmhjMwQ1380TUiDvP1hi91ulVbnbgUCtUVFEm+tR50ZhoOX5BiG1VoavRsfbBdr\nhiMzY8FuGZqUSGGCND2tdH7MQyl0/yRGgNlEOaGQ1rG+jjTCEsg0r1O0PhhXFqkG\n83yoQ3dptgJS/NFtV1uZM84TY+yJGjRTEyoVbtxHkrzgo4E2CuHUrWlm1nhN+Yjh\nDKz4AU7KxHTU7nKXC0EZVGQVzLYmvp1EreIhi3VleeH+Ias+IcNzxPZochCGdokT\nGO1Ze6s/AgMBAAECggEAAgG3CFZJQbFkofwdXO5CbHSimpOzCkdc7ry733r+o+Hg\nVxfsWYGTC9V9RGpVhMKQiDhoOACmzWkHmbQsv23/QBAEXJy7g6w+IknNrRqoXj9p\nfyN6mETdBceO7C7/cHZewoYhTNyocyUCWis1mrNDvyYFXTatoPui6PE4QMObgYsV\nKid0+iss+yePK7uUO5WUZ22tArtVljhLichhDz5n3gyumQSB0VA2if+uZ9DFb79l\n+/88rtwGAOfpNY5LO01VYliwMM4LboeY47JjVxzStbqpPIAZPf+1Q2zVhS5ZOKgH\ntEFrWYjB95A3Ey2X3i8QeeLR7uSgsohGFjq8pg3BYQKBgQDQSAxthFon/60+HTIi\nc7Oo4COSWI3ijA4Jwm/lqVuHJtt8g2FMPUD9bHVEpsa3RwceTIfs8Kj2sPzpfrpN\nVkSoErLp1pMB0frNUNvH36BpZT8mbtdniQP/wr+ZGaIH78LMAwp8gF8MPUdKpAP+\n0Yrs6aWHJcpX04MyYUda3UOn0QKBgQC/KC65G5gQrDL+usYfwGpDXPrrz6vkxmZe\n8+sHQwdcygWZtWJQuJ1OituRwCnAJFPTTYB5RKsH8EvGcYUBJ3s7Rs4W7odCpIH8\nhIw6oPBW1LBXucilmQUjo24A5Vz89B72wXfGYh/3RAgIVG/QCBvV1shdMSDQSGKW\na7NAnaL2DwKBgDUxAUOC1od6i2Lej+wugkZxn4QDa5Dc1cT2TB9p5f8ZFFqzLskK\np6tQ5I34za0GzbGWN+xx9aSyxJRZEfkoO/Z0eA6yBu8jEhsXOFnOKahg/ASzr/04\nB7ZspQPTgQbn22bArA/ptNxqVeehBYgxOXqRnP1r0EYntUzLfS6ebWXRAoGAbLkT\nEg+ixuDaRE3BADA1gEjzIopEj2NUuG7tX3z9RAZXdxxWZekK97A8wEJWvMUstEMh\nblfjGynOP3kzl/t3uLhF4X8biYj9sb1F8Na2u/xOrCar+5vz81gx6eqKoAjNT7Ws\nRTZsTfvwwaQc0Gq8QjzeSzr1GeIByOJK2taN6HsCgYB7w65m3ZlJ1u9AxFujmrM+\nSR/Fm8VtJDYM+uVwVCj3aBbDVmJlYM0vVTme+idvcoGcHNOgNWrqbgUXQDgQlLT0\nlVNZpR+MHIjD+OUmyMvpz31zl+7Xh1KX533Tevufh2Gmr2kHRqUEMkcRGMJlS5M3\neIddZpngr+GXbZXc1hmvvg==\n-----END PRIVATE KEY-----\n",
            'client_email' => 'dummy@example.com',
            'client_id' => '123',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/dummy%40example.com',
        ];
    }
}
