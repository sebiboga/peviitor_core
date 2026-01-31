<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/runner.php';
require_once __DIR__ . '/testplan.php';

function main(): int {
    global $SOLR_URL, $SOLR_USER, $SOLR_PASS, $TEST_PLAN, $MAX_EXPIRATION_DAYS;

    $docs = fetch_docs_with_curl($SOLR_URL, $SOLR_USER, $SOLR_PASS);
    if (empty($docs)) {
        fwrite(STDERR, "Nu s-a întors niciun document din Solr\n");
        return 1;
    }

    $ctx = [
        'MAX_EXPIRATION_DAYS' => $MAX_EXPIRATION_DAYS,
    ];

    // rulează testele și obține structura completă de rezultate
    $results = run_testplan($docs, $TEST_PLAN, $ctx);

    // scriem rezultatele brute într-un JSON, care va fi folosit de index.php + report.php
    $outFile = getenv('RESULTS_FILE') ?: __DIR__ . '/results.json';
    file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    fwrite(STDOUT, "Rezultate scrise în: {$outFile}\n");

    return 0;
}

exit(main());
