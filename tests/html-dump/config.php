<?php

// URL-ul core-ului Solr (fără user/pass în URL)
$SOLR_CORE_URL = getenv('SOLR_CORE_URL') ?: 'http://host.docker.internal:8983/solr/job';

// Auth pentru Solr (basic auth)
$SOLR_USER = getenv('SOLR_USER') ?: 'solr';
$SOLR_PASS = getenv('SOLR_PASS') ?: 'SolrRocks';

// Batch/paginare
$BATCH_SIZE = (int)(getenv('BATCH_SIZE') ?: 50);
$MAX_DOCS   = (int)(getenv('MAX_DOCS') ?: 0);

// Directorul din container unde salvăm HTML-urile
$OUTPUT_DIR = getenv('OUTPUT_DIR') ?: '/out';

// Comanda de bază pentru Chromium headless din container
$CHROME_BASE_CMD = 'chrome'
    . ' --headless=new'
    . ' --disable-gpu'
    . ' --no-sandbox'
    . ' --disable-dev-shm-usage'
    . ' --disable-setuid-sandbox'
    . ' --disable-web-security'
    . ' --disable-features=VizDisplayCompositor,AudioServiceOutOfProcess'
    . ' --disable-extensions'
    . ' --disable-plugins'
    . ' --disable-images'
    . ' --disable-background-timer-throttling'
    . ' --disable-backgrounding-occluded-windows'
    . ' --disable-renderer-backgrounding'
    . ' --disable-field-trial-config'
    . ' --disable-background-networking'
    . ' --user-agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"'
    . ' --virtual-time-budget=45000'
    . ' --dump-dom';
