<?php

namespace Tests\Unit;

use App\Models\Park;
use App\Services\FcmPushService;
use App\Services\FirebaseService;
use App\Services\MobileAlertService;
use Mockery;
use PHPUnit\Framework\TestCase;

class MobileAlertServiceTest extends TestCase
{
    public function test_notify_warden_and_public_formats_topic_correctly()
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $fcm = Mockery::mock(FcmPushService::class);

        // Expect the writes to fail or be ignored gracefully due to try/catch
        // but we specifically mock FcmPushService to verify topic names.
        $fcm->shouldReceive('sendToTopic')->with('warden_unknown', 'Title', 'Msg', 'SECURITY_ALERT', ['incidentId' => '123'])->once();
        $fcm->shouldReceive('sendToTopic')->with('park_alerts_unknown', 'Title', 'Msg', 'SECURITY_ALERT', ['incidentId' => '123'])->once();

        $firebase->shouldReceive('firestore')->andThrow(new \Exception('Mock Firestore exception'));

        $service = new MobileAlertService($firebase, $fcm);
        $service->notifyWardenAndPublic(null, '123', 'Title', 'Msg');
        
        $this->assertTrue(true);
    }
    
    public function test_notify_public_sos_formats_topic_correctly()
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $fcm = Mockery::mock(FcmPushService::class);

        $fcm->shouldReceive('sendToTopic')->with('park_alerts_unknown', 'Title', 'Msg', 'SECURITY_ALERT', ['sosId' => '456'])->once();

        $firebase->shouldReceive('firestore')->andThrow(new \Exception('Mock Firestore exception'));

        $service = new MobileAlertService($firebase, $fcm);
        $service->notifyPublicSos(null, '456', 'Title', 'Msg');
        
        $this->assertTrue(true);
    }
    
    public function test_notify_reporter_sighting_approved_fetches_tokens()
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $fcm = Mockery::mock(FcmPushService::class);

        $firebase->shouldReceive('fcmTokensFor')->with('uid789')->andReturn(['token1', 'token2']);
        $firebase->shouldReceive('firestore')->andThrow(new \Exception('Mock Firestore exception'));

        $fcm->shouldReceive('sendToTokens')->with(['token1', 'token2'], 'Title', 'Msg', 'SIGHTING_APPROVED', ['sightingId' => '101'])->once();

        $service = new MobileAlertService($firebase, $fcm);
        $service->notifyReporterSightingApproved('uid789', '101', 'Title', 'Msg');
        
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
