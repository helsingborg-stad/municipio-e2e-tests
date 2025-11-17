<?php

namespace Municipio\SmokeTests;

use Generator;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class SmokeTestE2E extends TestCase
{
    private static ?Client $httpClient = null;
    private static array $responses = [];
    private static array $urls = [];
    private static array $failedUrls = [];
    private static int $maxRetries = 2; // Dynamic number of retries, can be set externally
    private static int $delayMs = 500; // 0.5 second delay between requests

    public static function setUpBeforeClass(): void
    {
        if (empty(self::$urls)) {
            self::$urls = self::initUrls();
        }

        $client = self::getHttpClient();
        $options = self::getRequestOptions();
        $failed = [];

        // First pass: try all URLs
        foreach (self::$urls as $url) {
            try {
                $response = $client->get($url, $options);
                self::$responses[$url] = new class($response) {
                    private $response;
                    public function __construct($response) { $this->response = $response; }
                    public function wait() { return $this->response; }
                };
            } catch (\Exception $e) {
                self::$responses[$url] = new class($e) {
                    private $exception;
                    public function __construct($exception) { $this->exception = $exception; }
                    public function wait() { throw $this->exception; }
                };
                $failed[] = $url;
            }
            usleep(self::$delayMs * 1000);
        }

        // Retry logic
        $retries = 0;
        while (!empty($failed) && $retries < self::$maxRetries) {
            $retries++;
            $retryFailed = [];
            foreach ($failed as $url) {
                try {
                    $response = $client->get($url, $options);
                    self::$responses[$url] = new class($response) {
                        private $response;
                        public function __construct($response) { $this->response = $response; }
                        public function wait() { return $this->response; }
                    };
                } catch (\Exception $e) {
                    self::$responses[$url] = new class($e) {
                        private $exception;
                        public function __construct($exception) { $this->exception = $exception; }
                        public function wait() { throw $this->exception; }
                    };
                    $retryFailed[] = $url;
                }
                usleep(self::$delayMs * 1000);
            }
            $failed = $retryFailed;
        }

        self::$failedUrls = $failed;
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

        // if we were redirected to some other site, skip. We only want to test our own site.
        if($result->getStatusCode() === 301 && $result->hasHeader('Location') && strpos($result->getHeaderLine('Location'), parse_url($url, PHP_URL_HOST)) === false) {
            $this->assertTrue(true);
            return;
        }
        
        $this->assertHeaders($result->getHeaders(), $url);
        $this->assertContains($result->getStatusCode(), [200, 403, 410, 404], 'Unexpected status code: ' . $result->getStatusCode());
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

    private static function getRequestOptions(): array
    {
        return [
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => 20,
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
