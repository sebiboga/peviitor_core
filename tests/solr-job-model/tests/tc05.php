<?php

/**
 * TC05 – Job Model – Location validă (string sau array de string-uri)
 *
 * Scop:
 *  - Verifică faptul că, dacă `location` este prezent, este fie string, fie array de string-uri.
 *  - Permite diacritice românești (ex: „București”, „Cluj-Napoca”).
 *
 * Rezultat:
 *  - WARN dacă `location` lipsește (câmp opțional).
 *  - FAIL dacă `location` este prezent dar nu este string sau array de string-uri.
 *  - FAIL dacă este array dar conține elemente non-string.
 *  - PASS dacă `location` este conform.
 */
function tc05_job_location_if_present(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('location', $doc) || $doc['location'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'location lipsește (opțional)',
        ];
        return $findings;
    }

    $value = $doc['location'];

    // caz 1: string simplu
    if (is_string($value)) {
        $findings[] = [
            'status'  => 'PASS',
            'message' => 'location este string (ok pentru single-valued)',
        ];
        return $findings;
    }

    // caz 2: array de string-uri
    if (is_array($value)) {
        if ($value === []) {
            $findings[] = [
                'status'  => 'WARN',
                'message' => 'location este array gol',
            ];
            return $findings;
        }

        $allStrings = true;
        foreach ($value as $idx => $v) {
            if (!is_string($v)) {
                $allStrings = false;
                break;
            }
        }

        if ($allStrings) {
            $findings[] = [
                'status'  => 'PASS',
                'message' => 'location este array de string-uri (ok pentru multivalued)',
            ];
        } else {
            $findings[] = [
                'status'  => 'FAIL',
                'message' => 'location este array dar conține valori care nu sunt string-uri',
            ];
        }

        return $findings;
    }

    // orice alt tip = FAIL
    $findings[] = [
        'status'  => 'FAIL',
        'message' => 'location trebuie să fie string sau array de string-uri',
    ];

    return $findings;
}
