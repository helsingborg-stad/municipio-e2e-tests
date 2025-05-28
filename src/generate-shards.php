<?php


namespace Municipio\SmokeTests;

require_once __DIR__ . '/../vendor/autoload.php';

use Municipio\SmokeTests\GetUrlsFromSitemaps\GetUrlsFromSitemaps;
use Municipio\SmokeTests\SplitFileByLinesIntoShards\SplitFileByLinesIntoShards;
use Municipio\SmokeTests\WriteGeneratorOuputToFile\WriteGeneratorOuputToFile;

const SITEMAPS_ENV_VAR = 'SITEMAP_URLS';
const ALL_URLS_OUTPUT_FILE = __DIR__ . '/../output/urls.txt';

$sitemapUrls = getenv(SITEMAPS_ENV_VAR);

$urlsGenerator = new GetUrlsFromSitemaps( $sitemapUrls, 1, 3 );
$fileWriter = new WriteGeneratorOuputToFile();
$fileSplitter = new SplitFileByLinesIntoShards( ALL_URLS_OUTPUT_FILE );

$fileWriter->write($urlsGenerator->getUrlsFromSitemaps(), ALL_URLS_OUTPUT_FILE);
$fileSplitter->split();