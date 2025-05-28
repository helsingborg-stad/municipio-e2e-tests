<?php

namespace Municipio\SmokeTests;

// echo shard file names in github actions matrix format, e.g. ["urls-shard-1.txt","urls-shard-2.txt"]
echo json_encode(glob(__DIR__ . '/../output/urls-shard-*.txt'), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);