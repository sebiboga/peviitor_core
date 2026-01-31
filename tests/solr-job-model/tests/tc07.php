<?php

/**
 * TC07 – Job Model – Workmode enum
 *
 * Scop:
 *  - Verifică faptul că, dacă `workmode` este prezent, are una dintre
 *    valorile permise: "remote", "on-site", "hybrid".
 *
 * Rezultat:
 *  - WARN dacă `workmode` lipsește (câmp opțional).
 *  - FAIL dacă `workmode` are o valoare invalidă.
 *  - PASS dacă `workmode` este conform.
 */
function tc07_job_workmode_enum(array $doc, array $ctx): array {
    $findings = [];
    $allowed  = ['remote', 'on-site', 'hybrid'];

    if (!array_key_exists('workmode', $doc) || $doc['workmode'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'workmode lipsește (opțional)',
        ];
        return $findings;
    }

    if (!in_array($doc['workmode'], $allowed, true)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => "workmode invalid: {$doc['workmode']} (remote|on-site|hybrid)",
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'workmode ok'];
    }

    return $findings;
}
