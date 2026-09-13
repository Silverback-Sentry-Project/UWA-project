<?php

namespace App\Services;

// Guards the on-request image proxy (/api/media/proxy) against SSRF. The app emits
// Cloudinary secure_urls itself, so every proxied URL is expected to be one of ours -
// this guard exists to make sure a stray or tampered value can never be turned into a
// request to anything private, link-local, loopback, or metadata-adjacent.
//
// Mitigations beyond classification: the proxy disables redirects (so a benign-looking
// first hop can't hand us off to an internal address), caps response size, and only
// passes image/* content types through.
//
// The DNS resolver is injectable so the allow-path and hostname-classification paths are
// unit-testable offline (the same trick the original ww-website guard used).
class SsrfGuard
{
    /** @var callable(string): array<int,string> */
    private $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver ?? static function (string $host): array {
            $results = gethostbynamel($host);

            return $results === false ? [] : $results;
        };
    }

    /** @throws \InvalidArgumentException when the URL must not be fetched */
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Only http/https URLs are allowed.');
        }

        // parse_url() brackets literal IPv6 hosts ("[::1]") - strip them so the
        // IP-literal path classifies ::1 directly instead of treating it as a hostname.
        $host = strtolower(ltrim(rtrim($parts['host'] ?? '', ']'), '['));
        if ($host === '') {
            throw new \InvalidArgumentException('URL has no host.');
        }

        $isIpLiteral = filter_var($host, FILTER_VALIDATE_IP) !== false;
        if ($isIpLiteral) {
            if ($this->isBlockedIp($host)) {
                throw new \InvalidArgumentException('URL points to a private or reserved address.');
            }

            return;
        }

        if ($this->isBlockedHostname($host)) {
            throw new \InvalidArgumentException('Private or loopback hostnames are not allowed.');
        }

        $ips = call_user_func($this->resolver, $host);
        if ($ips === []) {
            throw new \InvalidArgumentException('Could not resolve host.');

        }
        foreach ($ips as $ip) {
            if ($this->isBlockedIp($ip)) {
                throw new \InvalidArgumentException('URL resolves to a private or reserved address.');
            }
        }
    }

    public function isBlockedHostname(string $host): bool
    {
        $host = strtolower(trim($host, '.'));

        return $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local');
    }

    public function isBlockedIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true;
        }

        if (strlen($packed) === 4) {
            return $this->isBlockedIpv4($ip);
        }

        return $this->isBlockedIpv6($packed);
    }

    private function isBlockedIpv4(string $ip): bool
    {
        [$a, $b, $c, $d] = array_map('intval', explode('.', $ip));

        // 0/8 "this network"; 10/8 RFC1918; 127/8 loopback; 169.254/16 link-local;
        // 172.16/12 RFC1918; 192.0.2/24 TEST-NET-1; 192.168/16 RFC1918;
        // 198.51.100/24 TEST-NET-2; 203.0.113/24 TEST-NET-3; >=224/4 multicast+reserved.
        return $a === 0
            || $a === 10
            || $a === 127
            || ($a === 169 && $b === 254)
            || ($a === 172 && $b >= 16 && $b <= 31)
            || ($a === 192 && $b === 168)
            || ($a === 192 && $b === 0 && $c === 2)
            || ($a === 198 && $b === 51 && $c === 100)
            || ($a === 203 && $b === 0 && $c === 113)
            || $a >= 224;
    }

    private function isBlockedIpv6(string $packed): bool
    {
        $bytes = array_values(unpack('C16', $packed));

        // :: and ::1 (unspecified / loopback).
        if ($bytes[0] === 0 && $bytes[1] === 0 && $bytes[2] === 0 && $bytes[3] === 0) {
            return true;
        }

        // fc00::/7 unique-local addressing.
        if (($bytes[0] & 0xFE) === 0xFC) {
            return true;
        }

        // fe80::/10 link-local.
        if ($bytes[0] === 0xFE && ($bytes[1] & 0xC0) === 0x80) {
            return true;
        }

        // ff00::/8 multicast.
        if ($bytes[0] === 0xFF) {
            return true;
        }

        // 2001:db8::/32 documentation range.
        if ($bytes[0] === 0x20 && $bytes[1] === 0x01 && $bytes[2] === 0x0D && $bytes[3] === 0xB8) {
            return true;
        }

        // ::ffff:a.b.c.d IPv4-mapped (bytes 0-9 zero, 10-11 = ff:ff) - classify the
        // embedded address rather than trusting the mapped form.
        if ($bytes[10] === 0xFF && $bytes[11] === 0xFF
            && array_slice($bytes, 0, 10) === array_fill(0, 10, 0)) {
            $mapped = sprintf('%d.%d.%d.%d', $bytes[12], $bytes[13], $bytes[14], $bytes[15]);

            return $this->isBlockedIpv4($mapped);
        }

        return false;
    }
}