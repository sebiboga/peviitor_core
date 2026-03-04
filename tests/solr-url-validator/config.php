<?php
// config.php

define('SOLR_CORE_URL', getenv('SOLR_CORE_URL') ?: 'http://host.docker.internal:8983/solr/job');

define('SOLR_USER', getenv('SOLR_USER') ?: 'solr');
define('SOLR_PASS', getenv('SOLR_PASS') ?: 'SolrRocks');

define('BATCH_SIZE', getenv('BATCH_SIZE') ?: 200);
define('MAX_DOCS', getenv('MAX_DOCS') ?: 0);

const INVALID_TEXT_PATTERNS = [
    'page no longer exists',
    'no longer available',
    'no longer valid',
    'job has expired',
    'this job is no longer',
];

define('URL_TIMEOUT', getenv('URL_TIMEOUT') ?: 15);


// Log file for deleted URLs (only in Docker, not locally)
$inDocker = is_dir('/app');
define('DELETED_LOG_FILE', $inDocker ? getenv('DELETED_LOG_FILE') ?: '/log/deleted_urls.log' : '');


