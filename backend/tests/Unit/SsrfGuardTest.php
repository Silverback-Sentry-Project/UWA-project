<?php

namespace Tests\Unit;

use App\Services\SsrfGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    #[DataProvider('blockedUrls')]
    public function test_assert_safe_rejects_private_or_local_targets(string $url): void
    {
        $guard = new SsrfGuard;

        $this->expectException(\InvalidArgumentException::class);

        $guard->assertSafe($url);
    }

    public static function blockedUrls(): array
    {
        return [
            'loopback ipv4' => ['http://127.0.0.1:8080/admin'],
            'link-local metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'rfc1918 class-a' => ['http://10.0.0.1/'],
            'rfc1918 class-b' => ['http://172.16.0.1/'],
            'rfc1918 class-c' => ['http://192.168.1.1/'],
            'testnet ipv4' => ['http://192.0.2.5/'],
            'multicast ipv4' => ['http://224.0.0.1/'],
            'reserved ipv4' => ['http://240.0.0.1/'],
            'unspecified ipv4' => ['http://0.0.0.0/'],
            'loopback ipv6' => ['http://[::1]/'],
            'unspecified ipv6' => ['http://[::]/'],
            'unique-local ipv6' => ['http://[fc00::1]/'],
            'link-local ipv6' => ['http://[fe80::1]/'],
            'multicast ipv6' => ['http://[ff02::1]/'],
            'documentation ipv6' => ['http://[2001:db8::1]/'],
            'ipv4-mapped loopback ipv6' => ['http://[::ffff:127.0.0.1]/'],
            'ipv4-mapped private ipv6' => ['http://[::ffff:10.0.0.1]/'],
            'localhost hostname' => ['http://localhost:8080/'],
            'subdomain of localhost' => ['http://internal.localhost/'],
            'mDNS hostname' => ['http://printer.local/'],
            'non-http scheme' => ['ftp://example.com/file'],
            'no host' => ['http:///path'],
        ];
    }

    public function test_assert_safe_allows_a_plain_public_ip_without_dns(): void
    {
        // 93.184.216.34 (example.com) is a public literal, so the guard must not even
        // consult DNS to approve it.
        $guard = new SsrfGuard(fn () => $this->fail('DNS must not be queried for IP literals.'));

        $guard->assertSafe('https://93.184.216.34/image.jpg');

        $this->addToAssertionCount(1);
    }

    public function test_assert_safe_allows_a_hostname_resolving_to_public_ips(): void
    {
        $guard = new SsrfGuard(fn (string $host) => $host === 'images.example.com' ? ['93.184.216.34'] : []);

        $guard->assertSafe('https://images.example.com/a.jpg');

        $this->addToAssertionCount(1);
    }

    public function test_assert_safe_rejects_a_hostname_resolving_anywhere_private(): void
    {
        // A single private answer among public ones is still an SSRF risk.
        $guard = new SsrfGuard(fn (string $host) => ['93.184.216.34', '10.0.0.5']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('resolves to a private or reserved address');

        $guard->assertSafe('https://dns-rebinding.example/a.jpg');
    }

    public function test_assert_safe_rejects_an_unresolvable_hostname(): void
    {
        $guard = new SsrfGuard(fn () => []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve host');

        $guard->assertSafe('https://does-not-exist.example/');
    }
}