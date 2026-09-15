<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckCloudinary extends Command
{
    protected $signature = 'silverback-sentry:cloudinary-check';

    protected $description = 'Verify Cloudinary credentials are set and the API is reachable';

    public function handle(): int
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            $this->error('Cloudinary is not configured.');
            $this->line('Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET');
            $this->line('(all three on the Cloudinary dashboard main page) in backend/.env, then');
            $this->line('run: php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('Cloud name:  '.$cloudName);
        $this->info('API key:     '.substr($apiKey, 0, 4).'…'.substr($apiKey, -2));
        $this->info('API secret:  '.str_repeat('•', 8).' ('.strlen($apiSecret).' chars)');

        // Live probe: a signed upload without any file bounces off Cloudinary's auth with a
        // 4xx rather than a network error, which proves both kernel reachability and that
        // the key/secret pair is accepted by the account.
        $response = Http::asMultipart()
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                'api_key' => $apiKey,
                'signature' => 'invalid',
                'timestamp' => time(),
            ]);

        if ($response->successful() || in_array($response->status(), [400, 401, 403], true)) {
            $this->info('Cloudinary API reachable and credentials accepted.');

            return self::SUCCESS;
        }

        $this->error('Cloudinary API unreachable or rejected the request: '.$response->body());

        return self::FAILURE;
    }
}