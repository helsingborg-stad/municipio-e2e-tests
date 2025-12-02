<?php

namespace Municipio\SmokeTests;

use Generator;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class SmokeTestE2E extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Municipio Smoke Test E2E'
            ],
        ]);
    }

    #[TestDox('Smoke test')]
    #[DataProvider('smokeTestProvider')]
    public function testSmokeTest(string $url): void
    {
        $result = $this->client->get($url);

        $body = $result->getBody();
        $html = '';
        while (!$body->eof()) {
            $html .= $body->read(1024);
        }

        // if we were redirected to some other site, skip. We only want to test our own site.
        if($result->getStatusCode() === 301 && $result->hasHeader('Location') && strpos($result->getHeaderLine('Location'), parse_url($url, PHP_URL_HOST)) === false) {
            $this->assertTrue(true);
            return;
        }
        
        $this->assertContains($result->getStatusCode(), [200, 403, 410, 404, 302, 301], 'Unexpected status code: ' . $result->getStatusCode());

        if($result->getStatusCode() !== 200) {
            return;
        }

        $this->assertHeaders($result->getHeaders(), $url);
        $this->assertStringNotContainsString('A view rendering issue has occurred', $html);
        $this->assertStringNotContainsString('<!-- Date component: Invalid date -->', $html);
    }

    private function assertHeaders(array $headers, string $url): void
    {
        $expectedOrigin = $this->getOriginFromUrl($url);

        $transportSecurityHeader = $headers['Strict-Transport-Security'] ?? $headers['strict-transport-security'] ?? null;
        $contentSecurityPolicyHeader = $headers['Content-Security-Policy'] ?? $headers['content-security-policy'] ?? null;
        $accessControlAllowOriginHeader = $headers['Access-Control-Allow-Origin'] ?? $headers['access-control-allow-origin'] ?? null;

        $this->assertNotNull($transportSecurityHeader, 'Strict-Transport-Security header is missing.');
        
        $this->assertNotNull($contentSecurityPolicyHeader, 'Content-Security-Policy header is missing.');

        $this->assertNotNull($accessControlAllowOriginHeader, 'Access-Control-Allow-Origin header is missing.');
        $this->assertEquals(
            $expectedOrigin,
            $accessControlAllowOriginHeader[0],
            'Access-Control-Allow-Origin header does not match the expected URL.'
        );
    }

    private function getOriginFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $origin = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $origin .= ':' . $parsedUrl['port'];
        }
        return $origin;
    }

    public static function smokeTestProvider(): Generator
    {
        foreach (self::initUrls() as $url) {
            yield $url => [$url];
        }
    }

    private static function decorateUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] . '&' : '';
        $parsedUrl['query'] = $query;

        return (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
            (isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
            (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '') .
            (isset($parsedUrl['path']) ? $parsedUrl['path'] : '');
    }

    private static function getUrlsFromShardFile(string $shardFile): array
    {
        if (!file_exists($shardFile)) {
            return [];
        }
        return file($shardFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private static function initUrls(): array
    {
        $urls = self::getUrlsFromShardFile(getenv('SHARD_FILE') ?: '');
        return array_map([self::class, 'decorateUrl'], $urls);
    }
}
