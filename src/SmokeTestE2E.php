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
        $html = (string)$result->getBody();
        $statusCode = $result->getStatusCode();

        // if we were redirected to some other site, skip. We only want to test our own site.
        if($statusCode === 301 && $result->hasHeader('Location') && strpos($result->getHeaderLine('Location'), parse_url($url, PHP_URL_HOST)) === false) {
            $this->assertTrue(true);
            return;
        }
        
        $this->assertContains($statusCode, [200, 403, 410, 404, 302, 301], 'Unexpected status code: ' . $statusCode);

        if($statusCode !== 200) {
            return;
        }

        $this->assertHeaders($result->getHeaders(), $url);
        $this->assertStringNotContainsString('A view rendering issue has occurred', $html);
        $this->assertStringNotContainsString('<!-- Date component: Invalid date -->', $html);
    }

    private function assertHeaders(array $headers, string $url): void
    {
        $expectedOrigin = $this->getOriginFromUrl($url);
        $headers = array_change_key_case($headers, CASE_LOWER);
        
        $this->assertArrayHasKey('strict-transport-security', $headers, 'Strict-Transport-Security header is missing.');
        $this->assertArrayHasKey('content-security-policy', $headers, 'Content-Security-Policy header is missing.');
        $this->assertArrayHasKey('access-control-allow-origin', $headers, 'Access-Control-Allow-Origin header is missing.');
        $this->assertEquals(
            $expectedOrigin,
            $headers['access-control-allow-origin'][0],
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
        foreach (file(getenv('SHARD_FILE') ?: '', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $url) {
            yield $url => [$url];
        }
    }
}
