<?php

/**
 * TC03 – Job Model – Company valid dacă există
 *
 * Scop:
 *  - Verifică faptul că, dacă `company` este prezent, este un string non-gol.
 *
 * Rezultat:
 *  - WARN dacă `company` lipsește (câmp opțional).
 *  - FAIL dacă `company` este prezent dar invalid.
 *  - PASS dacă `company` este conform.
 */
function tc03_job_company_if_present(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('company', $doc) || $doc['company'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'company lipsește (opțional, dar util pentru completitudine)',
        ];
        return $findings;
    }

    if (!is_string($doc['company']) || trim($doc['company']) === '') {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'company trebuie să fie string non-gol dacă există',
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'company ok'];
    }

    return $findings;
}
