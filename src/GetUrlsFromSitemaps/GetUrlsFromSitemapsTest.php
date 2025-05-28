<?php

namespace Municipio\SmokeTests\GetUrlsFromSitemaps;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class GetUrlsFromSitemapsTest extends TestCase
{
    #[TestDox('class can be instantiated')]
    public function testCanBeInstantiated(): void
    {
        $sitemapUrls = 'https://example.com/sitemap.xml';
        $getUrlsFromSitemaps = new GetUrlsFromSitemaps($sitemapUrls);
        $this->assertInstanceOf(GetUrlsFromSitemaps::class, $getUrlsFromSitemaps);
    }

    #[TestDox('getUrlsFromSitemaps returns a generator')]
    public function testGetUrlsFromSitemapsReturnsGenerator(): void
    {
        $sitemapUrls = 'https://example.com/sitemap.xml';
        $getUrlsFromSitemaps = new GetUrlsFromSitemaps($sitemapUrls, 0, 0);
        $generator = $getUrlsFromSitemaps->getUrlsFromSitemaps();
        $this->assertInstanceOf(\Generator::class, $generator);
    }

    #[TestDox('getUrlsFromSitemap returns a generator with URLs')]
    public function testGetUrlsFromSitemapReturnsGeneratorWithUrls(): void
    {
        $sitemapUrls = 'http://localhost:8000/sitemap.xml';
        $getUrlsFromSitemaps = new GetUrlsFromSitemaps($sitemapUrls, 0, 0);
        $generator = $getUrlsFromSitemaps->getUrlsFromSitemaps();

        $urls = iterator_to_array($generator);
        $this->assertNotEmpty($urls, 'Expected URLs to be returned from the sitemap.');
        
        foreach ($urls as $url) {
            $this->assertIsString($url, 'Each URL should be a string.');
            $this->assertNotEmpty($url, 'URL should not be empty.');
        }
    }
}
