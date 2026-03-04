<?php

// Config Solr – minim, totul vine din ENV
define('SOLR_CORE_URL', getenv('SOLR_CORE_URL') ?: '');
define('SOLR_USER', getenv('SOLR_USER') ?: '');
define('SOLR_PASS', getenv('SOLR_PASS') ?: '');
define('BATCH_SIZE', (int)(getenv('BATCH_SIZE') ?: 100));
define('MAX_DOCS', (int)(getenv('MAX_DOCS') ?: 0));
define('URL_TIMEOUT', (int)(getenv('URL_TIMEOUT') ?: 10));

// Câmpul de tags în indexul Solr (în JOBS: "tags": ["", "", ...])
define('TAGS_FIELD', getenv('TAGS_FIELD') ?: 'tags');

// Numărul maxim de tag-uri per job (model JOBS)
define('MAX_TAGS', (int)(getenv('MAX_TAGS') ?: 20));


// Groq config
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'groq/compound');


