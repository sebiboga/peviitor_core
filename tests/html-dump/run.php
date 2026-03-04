<?php

require __DIR__ . '/config.php';
require __DIR__ . '/solr_helpers.php';

echo "Starting HTML dump from Solr...\n";
echo "SOLR_CORE_URL = {$SOLR_CORE_URL}\n";
echo "OUTPUT_DIR    = {$OUTPUT_DIR}\n";
echo "BATCH_SIZE    = {$BATCH_SIZE}\n";
echo "MAX_DOCS      = {$MAX_DOCS}\n\n";

$totalFetched = 0;
$start = 0;
$batch = (int)$BATCH_SIZE;
$max   = (int)$MAX_DOCS;

while (true) {
    try {
        $resp = solrSelect($start, $batch);
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        break;
    }

    $docs = $resp['docs'];
    $numFound = $resp['numFound'];

    if (empty($docs)) {
        echo "No more documents (start = $start).\n";
        break;
    }

    echo "Fetched " . count($docs) . " docs (start = $start / numFound = $numFound).\n";

    foreach ($docs as $doc) {
        if (!isset($doc['url'])) {
            continue;
        }

        $url = $doc['url'];

        $idHash = md5($url);
        $outputFile = rtrim($OUTPUT_DIR, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . "job_{$idHash}.html";

        echo "Processing URL: $url\n";
        echo " -> $outputFile\n";

        try {
            fetchJobHtmlWithChrome($url, $outputFile);
            $totalFetched++;
        } catch (Exception $e) {
            echo "ERROR for $url: " . $e->getMessage() . "\n";
        }

        if ($max > 0 && $totalFetched >= $max) {
            echo "Reached MAX_DOCS limit: $max\n";
            break 2;
        }
    }

    $start += $batch;
    if ($start >= $numFound) {
        echo "Reached end of Solr documents.\n";
        break;
    }
}

echo "Done. Total fetched HTML files: $totalFetched\n";
