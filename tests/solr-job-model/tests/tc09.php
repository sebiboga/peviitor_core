<?php

/**
 * TC09 – Job Model – Status enum
 *
 * Scop:
 *  - Verifică faptul că, dacă `status` este prezent, este una dintre
 *    valorile: "scraped", "tested", "published", "verified".
 *
 * Rezultat:
 *  - WARN dacă `status` lipsește (opțional, dar recomandat).
 *  - FAIL dacă `status` este prezent dar invalid.
 *  - PASS dacă `status` este conform.
 */
function tc09_job_status_enum(array $doc, array $ctx): array {
    $findings = [];
    $allowed  = ['scraped', 'tested', 'published', 'verified'];

    if (!array_key_exists('status', $doc) || $doc['status'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'status lipsește (opțional, dar recomandat)',
        ];
        return $findings;
    }

    if (!in_array($doc['status'], $allowed, true)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => "status invalid: {$doc['status']} (scraped|tested|published|verified)",
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'status ok'];
    }

    return $findings;
}
