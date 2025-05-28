<?php

namespace Municipio\SmokeTests\GetUrlsFromSitemaps;

use Generator;

class GetUrlsFromSitemaps
{
    private static array $sitemapLoadAttempts = [];

    /**
     * Constructor to initialize the sitemap URLs.
     *
     * @param string $sitemapUrls Comma-separated list of sitemap URLs.
     */
    public function __construct(
        private string $sitemapUrls,
        private int $retryDelay = 10,
        private int $maxRetries = 1
    )
    {
    }

    public function getUrlsFromSitemaps(): Generator
    {
        $urls = [];

        foreach ($this->getSitemaps() as $sitemap) {
            foreach ($this->getUrlsFromSitemap($sitemap) as $url) {
                
                if( !in_array($url, $urls, true) ) {
                    yield $urls[] = $url;
                }
            }
        }
    }

    private function getUrlsFromSitemap(string $sitemapUrl): Generator
    {
        $sitemap = @simplexml_load_file($sitemapUrl);

        if ($sitemap === false) {
            if (count(array_keys(self::$sitemapLoadAttempts, $sitemapUrl)) < $this->maxRetries) {
                self::$sitemapLoadAttempts[] = $sitemapUrl;
                sleep($this->retryDelay);
                yield from $this->getUrlsFromSitemap($sitemapUrl);
            }
            throw new \RuntimeException("Failed to load sitemap: {$sitemapUrl}");
        }

        foreach ($sitemap->url as $urlNode) {
            yield (string)$urlNode->loc;
        }
    }

    private function getSitemaps(): Generator
    {
        foreach (explode(',', $this->sitemapUrls) as $sitemapUrl) {
            $url = filter_var(trim($sitemapUrl), FILTER_VALIDATE_URL);
            if ($url) {
                yield $url;
            }
        }
    }
}
