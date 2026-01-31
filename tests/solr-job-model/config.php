<?php

// Config Solr – suprascribil prin variabile de mediu
$SOLR_URL  = getenv('SOLR_URL') ?: 'http://host.docker.internal:8983/solr/job/select';
$SOLR_USER = getenv('SOLR_USER') ?: 'solr';
$SOLR_PASS = getenv('SOLR_PASS') ?: 'SolrRocks';

// Setări generale
$MAX_EXPIRATION_DAYS = 30;

// Tipuri de modele (pentru viitor: Job Model, Company Model etc.)
$MODEL_LABELS = [
    'job'     => 'Job Model',
    'company' => 'Company Model',
];
