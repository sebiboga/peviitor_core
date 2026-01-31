<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/testplan.php';

$STATUS_FILE = getenv('STATUS_FILE') ?: __DIR__ . '/status.json';

function fetch_docs_with_curl(string $solrUrl, string $user, string $pass): array {
    $ch = curl_init();

    $query = http_build_query([
        'q'      => '*:*',
        'q.op'   => 'OR',
        'indent' => 'true',
        'rows'   => 1000,
    ]);

    $url = $solrUrl . (strpos($solrUrl, '?') === false ? '?' : '&') . $query;

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => $user . ':' . $pass,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);

    $body   = curl_exec($ch);
    $errNo  = curl_errno($ch);
    $errStr = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errNo) {
        fwrite(STDERR, "Eroare curl: $errStr\n");
        return [];
    }
    if ($status < 200 || $status >= 300) {
        fwrite(STDERR, "Solr a răspuns cu HTTP $status\n");
        return [];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        fwrite(STDERR, "Nu s-a putut decoda JSON din răspunsul Solr\n");
        return [];
    }

    return $data['response']['docs'] ?? [];
}

// scrie status.json
function update_status(array $status): void {
    global $STATUS_FILE;
    file_put_contents($STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// helper ISO8601
function is_iso8601_utc(string $value): bool {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $value);
    return $d instanceof DateTimeImmutable && $d->format('Y-m-d\TH:i:s\Z') === $value;
}

// rulează toate testele din Test Plan și actualizează status.json pe parcurs
function run_testplan(array $docs, array $testPlan, array $ctx): array {
    $results = [
        'docs'    => [],
        'summary' => [
            'total_docs' => count($docs),
            'per_test'   => [],
        ],
    ];

    $completedTcs = [];

    foreach ($testPlan as $tc) {
        $results['summary']['per_test'][$tc['id']] = [
            'model'    => $tc['model'],
            'field'    => $tc['field'],
            'severity' => $tc['severity'],
            'pass'     => 0,
            'fail'     => 0,
            'warn'     => 0,
        ];
    }

    // status inițial
    update_status([
        'state'             => 'running',
        'current_tc'        => null,
        'current_desc'      => null,
        'current_doc_index' => 0,
        'total_docs'        => count($docs),
        'completed_tcs'     => $completedTcs,
    ]);

    foreach ($docs as $i => $doc) {
        $docId = isset($doc['url']) ? $doc['url'] : "idx:$i";
        $results['docs'][$i] = [
            'id'    => $docId,
            'tests' => [],
        ];
    }

    foreach ($testPlan as $tc) {
        $tcId   = $tc['id'];
        $fn     = $tc['function'];
        $model  = $tc['model'];

        // actualizează status pentru TC curent (progres pe documente va actualiza current_doc_index)
        update_status([
            'state'             => 'running',
            'current_tc'        => $tcId,
            'current_desc'      => "{$tcId} – {$model}",
            'current_doc_index' => 0,
            'total_docs'        => count($docs),
            'completed_tcs'     => $completedTcs,
        ]);

        if (!function_exists($fn)) {
            foreach ($results['docs'] as $idx => $dres) {
                $results['docs'][$idx]['tests'][$tcId] = [
                    'status'   => 'ERROR',
                    'messages' => ["Funcția de test {$fn} nu există"],
                ];
            }
            $results['summary']['per_test'][$tcId]['fail'] = count($docs);
            $completedTcs[$tcId] = 'FAIL';
            continue;
        }

        foreach ($docs as $i => $doc) {
            $findings = $fn($doc, $ctx);

            $statusOverall = 'PASS';
            $messages      = [];

            foreach ($findings as $f) {
                $messages[] = "{$f['status']}: {$f['message']}";
                if ($f['status'] === 'FAIL') {
                    $statusOverall = 'FAIL';
                } elseif ($f['status'] === 'WARN' && $statusOverall !== 'FAIL') {
                    $statusOverall = 'WARN';
                }
            }

            $results['docs'][$i]['tests'][$tcId] = [
                'status'   => $statusOverall,
                'messages' => $messages,
            ];

            if ($statusOverall === 'FAIL') {
                $results['summary']['per_test'][$tcId]['fail']++;
            } elseif ($statusOverall === 'WARN') {
                $results['summary']['per_test'][$tcId]['warn']++;
            } else {
                $results['summary']['per_test'][$tcId]['pass']++;
            }

            // progres pe document
            update_status([
                'state'             => 'running',
                'current_tc'        => $tcId,
                'current_desc'      => "{$tcId} – {$model}",
                'current_doc_index' => $i + 1,
                'total_docs'        => count($docs),
                'completed_tcs'     => $completedTcs,
            ]);
        }

        // după ce TC e complet, setăm sumar pentru TC
        $tcSummary = $results['summary']['per_test'][$tcId];
        if ($tcSummary['fail'] > 0) {
            $completedTcs[$tcId] = 'FAIL';
        } elseif ($tcSummary['warn'] > 0) {
            $completedTcs[$tcId] = 'WARN';
        } else {
            $completedTcs[$tcId] = 'PASS';
        }

        update_status([
            'state'             => 'running',
            'current_tc'        => null,
            'current_desc'      => null,
            'current_doc_index' => 0,
            'total_docs'        => count($docs),
            'completed_tcs'     => $completedTcs,
        ]);
    }

    // status final
    update_status([
        'state'             => 'finished',
        'current_tc'        => null,
        'current_desc'      => null,
        'current_doc_index' => 0,
        'total_docs'        => count($docs),
        'completed_tcs'     => $completedTcs,
    ]);

    return $results;
}
