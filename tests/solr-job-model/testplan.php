<?php

require_once __DIR__ . '/config.php';

// includem toate testele pentru Job Model
require_once __DIR__ . '/tests/tc01.php';
require_once __DIR__ . '/tests/tc02.php';
require_once __DIR__ . '/tests/tc03.php';
require_once __DIR__ . '/tests/tc04.php';
require_once __DIR__ . '/tests/tc05.php';
require_once __DIR__ . '/tests/tc06.php';
require_once __DIR__ . '/tests/tc07.php';
require_once __DIR__ . '/tests/tc08.php';
require_once __DIR__ . '/tests/tc09.php';
require_once __DIR__ . '/tests/tc10.php';
require_once __DIR__ . '/tests/tc11.php';
require_once __DIR__ . '/tests/tc12.php';

$TEST_PLAN = [
    ['id' => 'TC01', 'model' => 'job', 'field' => 'url',            'severity' => 'ERROR', 'function' => 'tc01_job_url_required'],
    ['id' => 'TC02', 'model' => 'job', 'field' => 'title',          'severity' => 'ERROR', 'function' => 'tc02_job_title_required'],
    ['id' => 'TC03', 'model' => 'job', 'field' => 'company',        'severity' => 'WARN',  'function' => 'tc03_job_company_if_present'],
    ['id' => 'TC04', 'model' => 'job', 'field' => 'cif',            'severity' => 'WARN',  'function' => 'tc04_job_cif_if_present'],
    ['id' => 'TC05', 'model' => 'job', 'field' => 'location',       'severity' => 'WARN',  'function' => 'tc05_job_location_if_present'],
    ['id' => 'TC06', 'model' => 'job', 'field' => 'tags',           'severity' => 'WARN',  'function' => 'tc06_job_tags_structure'],
    ['id' => 'TC07', 'model' => 'job', 'field' => 'workmode',       'severity' => 'WARN',  'function' => 'tc07_job_workmode_enum'],
    ['id' => 'TC08', 'model' => 'job', 'field' => 'date',           'severity' => 'WARN',  'function' => 'tc08_job_date_iso8601'],
    ['id' => 'TC09', 'model' => 'job', 'field' => 'status',         'severity' => 'WARN',  'function' => 'tc09_job_status_enum'],
    ['id' => 'TC10', 'model' => 'job', 'field' => 'vdate',          'severity' => 'WARN',  'function' => 'tc10_job_vdate_when_verified'],
    ['id' => 'TC11', 'model' => 'job', 'field' => 'expirationdate', 'severity' => 'WARN',  'function' => 'tc11_job_expiration_vs_vdate'],
    ['id' => 'TC12', 'model' => 'job', 'field' => 'salary',         'severity' => 'WARN',  'function' => 'tc12_job_salary_format'],
];
