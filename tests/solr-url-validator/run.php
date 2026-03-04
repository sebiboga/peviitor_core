<?php
require __DIR__ . '/config.php';





$logDir = dirname(DELETED_LOG_FILE);
if (DELETED_LOG_FILE && $logDir && !is_dir($logDir)) {

    mkdir($logDir, 0777, true);
}

function solrGet($params)
{
    $url = SOLR_CORE_URL . '/select?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (SOLR_USER && SOLR_PASS) {
        curl_setopt($ch, CURLOPT_USERPWD, SOLR_USER . ':' . SOLR_PASS);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        fwrite(STDERR, "Solr GET error: " . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        fwrite(STDERR, "Solr GET HTTP code: $httpCode" . PHP_EOL);
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        fwrite(STDERR, "Solr GET JSON decode error" . PHP_EOL);
        return null;
    }

    return $data;
}

// delete by URL (unique key) + log to file
function solrDeleteByUrl($urlValue)
{
    $url = SOLR_CORE_URL . '/update?commit=true';
    $payload = json_encode(['delete' => ['query' => 'url:"' . addslashes($urlValue) . '"']]);

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
        fwrite(STDERR, "Solr DELETE error for $urlValue: " . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Deleted from Solr: $urlValue" . PHP_EOL;

        // append to log file: timestamp | url


        if (DELETED_LOG_FILE) {
            file_put_contents(
                DELETED_LOG_FILE,
                date('c') . " | " . $urlValue . PHP_EOL,
                FILE_APPEND
            );
        }


        return true;
    }

    fwrite(STDERR, "Solr DELETE HTTP code $httpCode for $urlValue" . PHP_EOL);
    return false;
}

function checkUrl($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => URL_TIMEOUT,
        CURLOPT_TIMEOUT        => URL_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'PeViitorUrlChecker/1.0',
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        fwrite(STDERR, "cURL error for $url: $error" . PHP_EOL);
        return ['status' => 'error', 'http_code' => null];
    }

    if ($httpCode == 404) {
        return ['status' => 'invalid', 'http_code' => 404];
    }

    if ($httpCode == 200) {
        $lower = mb_strtolower($body);
        foreach (INVALID_TEXT_PATTERNS as $pattern) {
            if (strpos($lower, mb_strtolower($pattern)) !== false) {
                return ['status' => 'invalid', 'http_code' => 200];
            }
        }
        return ['status' => 'valid', 'http_code' => 200];
    }

    if (in_array($httpCode, [410, 403, 500, 503])) {
        return ['status' => 'invalid', 'http_code' => $httpCode];
    }

    return ['status' => 'unknown', 'http_code' => $httpCode];
}

function validateAllDocsOnce()
{
    $start = 0;
    $processed = 0;
    $deleted = 0;

    echo "Starting validation cycle against: " . SOLR_CORE_URL . PHP_EOL;

    while (true) {
        if (MAX_DOCS > 0 && $processed >= MAX_DOCS) {
            echo "Reached MAX_DOCS limit (" . MAX_DOCS . "), stopping cycle." . PHP_EOL;
            break;
        }

        $data = solrGet([
            'q'     => '*:*',
            'fl'    => 'url',
            'rows'  => BATCH_SIZE,
            'start' => $start,
        ]);

        if (!$data || !isset($data['response']['docs'])) {
            echo "No more docs or error from Solr." . PHP_EOL;
            break;
        }

        $docs = $data['response']['docs'];
        if (empty($docs)) {
            echo "No more docs to process." . PHP_EOL;
            break;
        }

        foreach ($docs as $doc) {
            if (MAX_DOCS > 0 && $processed >= MAX_DOCS) {
                break 2;
            }

            $processed++;
            $url = $doc['url'] ?? null;
            if (!$url) {
                continue;
            }

            echo "[$processed] Checking: $url" . PHP_EOL;
            $result = checkUrl($url);

            if ($result['status'] === 'invalid') {
                echo " -> INVALID (HTTP " . $result['http_code'] . "), deleting..." . PHP_EOL;
                if (solrDeleteByUrl($url)) {
                    $deleted++;
                }
            } elseif ($result['status'] === 'valid') {
                echo " -> OK" . PHP_EOL;
            } else {
                echo " -> UNKNOWN (HTTP " . $result['http_code'] . "), keeping." . PHP_EOL;
            }
        }

        $start += BATCH_SIZE;
    }

    echo "Cycle finished. Processed: $processed, Deleted: $deleted" . PHP_EOL;
}

while (true) {
    $startedAt = date('c');
    echo "=== Validation cycle started at $startedAt ===" . PHP_EOL;

    validateAllDocsOnce();

    $finishedAt = date('c');
    echo "=== Validation cycle finished at $finishedAt, sleeping 1h ===" . PHP_EOL;

    sleep(3600);
}
