<?php

require __DIR__ . '/config.php';

/**
 * SELECT în Solr cu paginație și basic auth.
 */
function solrSelect(int $start, int $rows): array
{
    global $SOLR_CORE_URL, $SOLR_USER, $SOLR_PASS;

    $url = rtrim($SOLR_CORE_URL, '/') . '/select?' . http_build_query([
        'q'    => '*:*',
        'wt'   => 'json',
        'rows' => $rows,
        'start'=> $start,
    ]);

    $auth = base64_encode($SOLR_USER . ':' . $SOLR_PASS);

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header'  => "Authorization: Basic {$auth}\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        throw new Exception("Cannot fetch Solr: $url");
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['response']['docs'])) {
        throw new Exception("Invalid Solr response");
    }

    $response = $data['response'];

    return [
        'numFound' => isset($response['numFound']) ? (int)$response['numFound'] : 0,
        'docs'     => $response['docs'],
    ];
}

/**
 * Rulează Chromium headless din container și salvează HTML-ul.
 * stdout -> fișier HTML, stderr -> /dev/null (nu mai poluează fișierul).
 */
function fetchJobHtmlWithChrome(string $url, string $outputPath): void
{
    global $CHROME_BASE_CMD;

    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new Exception("Cannot create directory: $dir");
        }
    }

    // stdout -> fișier, stderr -> /dev/null
    $cmd = $CHROME_BASE_CMD
        . ' ' . escapeshellarg($url)
        . ' > ' . escapeshellarg($outputPath)
        . ' 2>/dev/null';

    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0) {
        throw new Exception("Chrome failed for $url (exit code $exitCode)");
    }
}
