<?php

namespace Municipio\SmokeTests;

use Generator;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class SmokeTestE2E extends TestCase
{
    private static ?Client $httpClient = null;
    private static array $responses = [];
     private static array $urls = [];

    public static function setUpBeforeClass(): void
    {
                if (empty(self::$urls)) {
            self::$urls = self::initUrls();
        }

        $client = self::getHttpClient();
        $options = self::getRequestOptions();

        foreach (self::$urls as $url) {
            self::$responses[$url] = $client->getAsync($url, $options);
        }

        \GuzzleHttp\Promise\Utils::settle(self::$responses)->wait();
    }

    #[TestDox('Smoke test')]
    #[DataProvider('smokeTestProvider')]
    public function testSmokeTest(string $url): void
    {
        $promise = self::$responses[$url];
        $result = $promise->wait();

        $body = $result->getBody();
        $html = '';
        while (!$body->eof()) {
            $html .= $body->read(1024);
        }

        $this->assertHeaders($result->getHeaders(), $url);
        $this->assertContains($result->getStatusCode(), [200, 403, 410], 'Unexpected status code: ' . $result->getStatusCode());
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
        $this->assertStrictTransportSecurity($transportSecurityHeader[0]);
        
        $this->assertNotNull($contentSecurityPolicyHeader, 'Content-Security-Policy header is missing.');

        $this->assertNotNull($accessControlAllowOriginHeader, 'Access-Control-Allow-Origin header is missing.');
        $this->assertEquals(
            $expectedOrigin,
            $accessControlAllowOriginHeader[0],
            'Access-Control-Allow-Origin header does not match the expected URL.'
        );
    }


    private function assertStrictTransportSecurity(string $headerValue): void
    {
        $this->assertStringContainsString('max-age=', $headerValue, 'Strict-Transport-Security header does not contain max-age.');

        if (preg_match('/max-age=(\d+)/', $headerValue, $matches)) {
            $maxAge = (int)$matches[1];
            $this->assertGreaterThanOrEqual(31536000, $maxAge, 'Strict-Transport-Security max-age is less than 1 year.');
        } else {
            $this->fail('Strict-Transport-Security header does not contain a valid max-age value.');
        }
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

    private static function getRequestOptions(): array
    {
        return [
            'http_errors' => false,
            'allow_redirects' => true,
            'timeout' => 100,
            'headers' => [
                'User-Agent' => 'Municipio Smoke Test E2E'
            ],
        ];
    }

    public static function smokeTestProvider(): Generator
    {
        if (empty(self::$urls)) {
            self::$urls = self::initUrls();
        }

        foreach (self::$urls as $url) {
            yield $url => [$url];
        }
    }

    private static function decorateUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] . '&' : '';
        $query .= 'debug=1&pw_test=1';
        $parsedUrl['query'] = $query;

        return (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
            (isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
            (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '') .
            (isset($parsedUrl['path']) ? $parsedUrl['path'] : '') .
            '?' . $parsedUrl['query'];
    }

    private static function getUrlsFromShardFile(string $shardFile): array
    {
        if (!file_exists($shardFile)) {
            return [];
        }
        return file($shardFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private static function getHttpClient(): Client
    {
        return self::$httpClient ??= new Client(['timeout' => 20.0]);
    }

    private static function initUrls(): array
    {
        $urls = self::getUrlsFromShardFile(getenv('SHARD_FILE') ?: '');
        return array_map([self::class, 'decorateUrl'], $urls);
    }
}
