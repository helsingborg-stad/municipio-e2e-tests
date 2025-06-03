<?php


namespace Municipio\SmokeTests;

require_once __DIR__ . '/../vendor/autoload.php';

use Municipio\SmokeTests\GetUrlsFromSitemaps\GetUrlsFromSitemaps;
use Municipio\SmokeTests\SplitFileByLinesIntoShards\SplitFileByLinesIntoShards;
use Municipio\SmokeTests\WriteGeneratorOuputToFile\WriteGeneratorOuputToFile;

const SITEMAPS_ENV_VAR = 'SITEMAP_URLS';
const OUTPUT_DIR = __DIR__ . '/../output';
const ALL_URLS_OUTPUT_FILE = OUTPUT_DIR . '/urls.txt';

$sitemapUrls = getenv(SITEMAPS_ENV_VAR);

// Clear folder contents.
array_map('unlink', glob(OUTPUT_DIR . '/*.*'));

$urlsGenerator = new GetUrlsFromSitemaps( $sitemapUrls, 1, 3 );
$fileWriter = new WriteGeneratorOuputToFile();
$fileSplitter = new SplitFileByLinesIntoShards( ALL_URLS_OUTPUT_FILE, 100 );

$fileWriter->write($urlsGenerator->getUrlsFromSitemaps(), ALL_URLS_OUTPUT_FILE);
$fileSplitter->split();