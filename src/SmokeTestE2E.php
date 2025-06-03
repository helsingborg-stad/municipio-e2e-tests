<?php

namespace Municipio\SmokeTests;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class SmokeTestE2E extends TestCase
{
    private static ?Client $httpClient = null;

    #[TestDox('Smoke test')]
    #[DataProvider('smokeTestProvider')]
    public function testSmokeTest(string $url): void
    {
        $response = self::getHttpClient()->getAsync($url, $this->getRequestOptions())->wait();

        // Ensure the response is an instance of Response
        $this->assertInstanceOf(Response::class, $response, 'Expected a Guzzle Response object.');

        $body = $response->getBody();
        $html = '';
        while (!$body->eof()) {
            $html .= $body->read(1024); // stream in chunks
        }

        $this->assertContains($response->getStatusCode(), [200, 403, 410], 'Expected status code 200 or 410, got: ' . $response->getStatusCode());
        $this->assertStringNotContainsString('A view rendering issue has occurred', $html, 'Found a view rendering issue in the response.');
        $this->assertStringNotContainsString('<!-- Date component: Invalid date -->', $html, 'Found an invalid date component in the response');
    }

    private function getRequestOptions():array {
        return [
            'http_errors' => false, 
            'allow_redirects' => true,
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Municipio Smoke Test E2E'
            ],
        ];
    }

    public static function smokeTestProvider(): Generator
    {
        $shardFile = getenv('SHARD_FILE');

        if (empty($shardFile)) {
            return;
        }

        $urls = self::getUrlsFromShardFile($shardFile);
        $urls = array_map([self::class, 'decorateUrl'], $urls);

        foreach ($urls as $url) {
            yield $url => [$url];
        }
    }

    private static function decorateUrl(string $url): string
    {
        // ensure that url has get params debug and pw_test
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

    private static function getUrlsFromShardFile(string $shardFile):array {
        if (!file_exists($shardFile)) {
            return [];
        }
        return file($shardFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private static function getHttpClient(): Client
    {
        return self::$httpClient ??= new Client(['timeout' => 10.0]);
    }
}
