<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/groq.php';

if (!defined('SOLR_CORE_URL') || SOLR_CORE_URL === '') {
    fwrite(STDERR, "SOLR_CORE_URL is not set.\n");
    exit(1);
}

$batchSize = (int)BATCH_SIZE;
$maxDocs   = (int)MAX_DOCS;

echo "Starting solr-tag-updater...\n";
echo "SOLR_CORE_URL: " . SOLR_CORE_URL . "\n";
echo "BATCH_SIZE: $batchSize, MAX_DOCS: $maxDocs\n";

$totalProcessed = 0;
$start = 0;

while (true) {
    if ($maxDocs > 0 && $totalProcessed >= $maxDocs) {
        echo "Reached MAX_DOCS limit: $maxDocs\n";
        break;
    }

    $rows = $batchSize;
    if ($maxDocs > 0) {
        $remaining = $maxDocs - $totalProcessed;
        if ($remaining < $rows) {
            $rows = $remaining;
        }
    }

    $docs = fetchDocsFromSolr($start, $rows);
    $count = count($docs);

    if ($count === 0) {
        echo "No more documents.\n";
        break;
    }

    echo "Fetched $count documents from Solr (start=$start).\n";

    foreach ($docs as $doc) {
        $totalProcessed++;
        $url = $doc['url'] ?? null;
        if (!$url) {
            continue;
        }

        $title = $doc['title'] ?? '';

        // 1. Download HTML-ul randat cu JS (Puppeteer)
        $html = fetchRenderedHtml($url);
        if ($html === '') {
            fwrite(STDERR, "Failed to fetch rendered html for url: $url\n");
            continue;
        }

        // DEBUG: afișăm primele caractere din HTML
        echo "\n==== RAW RENDERED HTML for URL: $url ====\n";
        echo substr($html, 0, 1000) . "\n";
        echo "==== END RAW RENDERED HTML ====\n";

        // 2. Folosim toată pagina randată ca input pentru AI
        $pageHtml = $html;

        // 3. Calculăm tag-urile cu Groq (prompt AI)
        $tags = groqExtractTags($title, $pageHtml);

        if (empty($tags)) {
            fwrite(STDERR, "No tags extracted for url: $url\n");
            continue;
        }

        // DEBUG: vedem tag-urile primite
        echo "\n==== TAGS for URL: $url ====\n";
        var_dump($tags);
        echo "==== END TAGS ====\n";

        // 4. Scriem tag-urile în Solr, pe documentul cu acest url
        $ok = solrUpdateTagsByUrl($url, $tags);
        if (!$ok) {
            fwrite(STDERR, "Failed to update tags for url: $url\n");
        }
    }

    $start += $count;
}

echo "Done. Total processed: $totalProcessed\n";


function fetchDocsFromSolr(int $start, int $rows): array
{
    // Job-uri fără tags; filtrele le poți ajusta după nevoie
    $params = [
        'q'  => '*:*',
        'fq' => [
            'status:scraped',
            '-' . TAGS_FIELD . ':[* TO *]'
        ],
        'fl' => 'url,title',
        'start' => $start,
        'rows'  => $rows,
    ];

    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $url = SOLR_CORE_URL . '/select?' . $query;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, URL_TIMEOUT);
    if (SOLR_USER && SOLR_PASS) {
        curl_setopt($ch, CURLOPT_USERPWD, SOLR_USER . ':' . SOLR_PASS);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        fwrite(STDERR, "Solr SELECT error: " . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        return [];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        fwrite(STDERR, "Solr SELECT HTTP code $httpCode\n");
        return [];
    }

    $data = json_decode($response, true);
    if (!isset($data['response']['docs']) || !is_array($data['response']['docs'])) {
        return [];
    }

    return $data['response']['docs'];
}

function solrUpdateTagsByUrl(string $urlValue, array $tags): bool
{
    $url = SOLR_CORE_URL . '/update?commit=true';

    $doc = [
        'url' => $urlValue, // uniqueKey
        TAGS_FIELD => ['set' => $tags],
    ];

    $payload = json_encode([$doc]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if (SOLR_USER && SOLR_PASS) {
        curl_setopt($ch, CURLOPT_USERPWD, SOLR_USER . ':' . SOLR_PASS);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        fwrite(STDERR, "Solr UPDATE error for $urlValue: " . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Updated tags in Solr for: $urlValue\n";
        return true;
    }

    fwrite(STDERR, "Solr UPDATE HTTP code $httpCode for $urlValue\n");
    return false;
}

/**
 * Download HTML randat (execută JS) pentru un URL de job – apelăm Puppeteer în container.
 */
function fetchRenderedHtml(string $url): string
{
    $nodeScript = __DIR__ . '/render_page.js';

    $cmd = sprintf(
        'node %s %s 2>&1',
        escapeshellarg($nodeScript),
        escapeshellarg($url)
    );

    $output = shell_exec($cmd);
    if ($output === null) {
        fwrite(STDERR, "Puppeteer failed for url: $url\n");
        return '';
    }

    return $output;
}
