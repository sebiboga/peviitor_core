<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/runner.php';
require_once __DIR__ . '/report.php';
require_once __DIR__ . '/testplan.php';

function main(): int {
    global $SOLR_URL, $SOLR_USER, $SOLR_PASS, $TEST_PLAN, $MODEL_LABELS, $MAX_EXPIRATION_DAYS;

    $docs = fetch_docs_with_curl($SOLR_URL, $SOLR_USER, $SOLR_PASS);

    if (empty($docs)) {
        fwrite(STDERR, "Nu s-a întors niciun document din Solr\n");
        return 1;
    }

    $ctx = [
        'MAX_EXPIRATION_DAYS' => $MAX_EXPIRATION_DAYS,
    ];

    $results = run_testplan($docs, $TEST_PLAN, $ctx);

    $html = render_html_report($results, $TEST_PLAN, $MODEL_LABELS);

    $outFile = getenv('REPORT_FILE') ?: __DIR__ . '/report.html';
    file_put_contents($outFile, $html);

    fwrite(STDOUT, "Raport generat în: {$outFile}\n");

    return 0;
}

exit(main());
