<?php

/**
 * TC05 – Job Model – Location string validă
 *
 * Scop:
 *  - Verifică faptul că, dacă `location` este prezent, este un string.
 *  - Permite diacritice românești (ex: „București”, „Cluj-Napoca”).
 *
 * Rezultat:
 *  - WARN dacă `location` lipsește (câmp opțional).
 *  - FAIL dacă `location` este prezent dar nu este string.
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

    if (!is_string($doc['location'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'location trebuie să fie string (nu array)',
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'location ok'];
    }

    return $findings;
}
