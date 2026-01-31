<?php

/**
 * TC01 – Job Model – URL required & valid
 *
 * Scop:
 *  - Verifică faptul că fiecare document are câmpul `url` prezent (required).
 *  - Verifică faptul că `url` este un URL HTTP/HTTPS valid.
 *  - Verifică faptul că `url` începe cu `http://` sau `https://`.
 *
 * Rezultat:
 *  - FAIL dacă `url` lipsește sau este invalid.
 *  - PASS dacă `url` trece toate verificările.
 */
function tc01_job_url_required(array $doc, array $ctx): array {
    $findings = [];

    if (!isset($doc['url']) || $doc['url'] === '') {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'url lipsă (câmp required)',
        ];
        return $findings;
    }

    $url = $doc['url'];

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'url invalid (nu e URL HTTP/HTTPS valid)',
        ];
    }
    if (!preg_match('#^https?://#i', $url)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'url trebuie să înceapă cu http/https',
        ];
    }

    if (empty($findings)) {
        $findings[] = ['status' => 'PASS', 'message' => 'url ok'];
    }

    return $findings;
}
