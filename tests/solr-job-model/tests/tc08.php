<?php

/**
 * TC08 – Job Model – Date ISO8601 UTC
 *
 * Scop:
 *  - Verifică faptul că, dacă `date` este prezent, este un timestamp
 *    ISO8601 UTC valid (ex: "2026-01-18T10:00:00Z").
 *
 * Rezultat:
 *  - WARN dacă `date` lipsește (opțional, dar recomandat).
 *  - FAIL dacă `date` este prezent dar invalid.
 *  - PASS dacă `date` este conform.
 */
function tc08_job_date_iso8601(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('date', $doc) || $doc['date'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'date lipsește (opțional, dar ideal prezentă)',
        ];
        return $findings;
    }

    if (!is_iso8601_utc($doc['date'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'date nu este ISO8601 UTC valid (ex: 2026-01-18T10:00:00Z)',
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'date ok'];
    }

    return $findings;
}
