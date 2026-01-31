<?php

/**
 * TC04 – Job Model – CIF valid dacă există
 *
 * Scop:
 *  - Verifică faptul că, dacă `cif` este prezent, este un string non-gol.
 *
 * Rezultat:
 *  - WARN dacă `cif` lipsește (câmp opțional).
 *  - FAIL dacă `cif` este prezent dar invalid.
 *  - PASS dacă `cif` este conform.
 */
function tc04_job_cif_if_present(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('cif', $doc) || $doc['cif'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'cif lipsește (opțional)',
        ];
        return $findings;
    }

    if (!is_string($doc['cif']) || trim($doc['cif']) === '') {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'cif trebuie să fie string non-gol dacă există',
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'cif ok'];
    }

    return $findings;
}
