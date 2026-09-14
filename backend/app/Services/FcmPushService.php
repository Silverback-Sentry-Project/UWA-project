<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

class FcmPushService
{
    public function __construct(
        private readonly FirebaseService $firebase,
        private readonly LoggerInterface $logger
    ) {}

    public function sendToTopic(string $topic, string $title, string $body, string $type, array $data = []): void
    {
        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_merge(['type' => $type], $data));

            $this->firebase->messaging()->send($message);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send FCM topic notification', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendToTokens(array $tokens, string $title, string $body, string $type, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData(array_merge(['type' => $type], $data));

            $this->firebase->messaging()->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send FCM token notifications', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
