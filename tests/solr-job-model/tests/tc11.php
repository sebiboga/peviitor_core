<?php

/**
 * TC11 – Job Model – Expirationdate corelată cu vdate
 *
 * Scop:
 *  - Verifică faptul că, dacă `expirationdate` este prezent, este ISO8601 UTC valid.
 *  - Verifică faptul că, dacă și `vdate` este prezentă și validă,
 *    atunci `expirationdate` nu este cu mai mult de N zile (ex: 30) după `vdate`.
 *
 * Rezultat:
 *  - WARN dacă `expirationdate` lipsește (opțional, dar ideal prezentă).
 *  - FAIL dacă `expirationdate` este invalidă sau mai mare decât vdate+N zile.
 *  - PASS dacă `expirationdate` este conformă.
 */
function tc11_job_expiration_vs_vdate(array $doc, array $ctx): array {
    $findings = [];
    $maxDays  = $ctx['MAX_EXPIRATION_DAYS'];

    if (!array_key_exists('expirationdate', $doc) || $doc['expirationdate'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'expirationdate lipsește (ideal setat)',
        ];
        return $findings;
    }

    if (!is_iso8601_utc($doc['expirationdate'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'expirationdate nu este ISO8601 UTC valid',
        ];
        return $findings;
    }

    if (empty($doc['vdate']) || !is_iso8601_utc($doc['vdate'])) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'expirationdate set, dar vdate lipsește sau e invalidă (nu putem verifica +N zile)',
        ];
        return $findings;
    }

    $v = new DateTimeImmutable($doc['vdate']);
    $e = new DateTimeImmutable($doc['expirationdate']);
    $diffSeconds = $e->getTimestamp() - $v->getTimestamp();
    $maxSeconds  = $maxDays * 24 * 3600;

    if ($diffSeconds > $maxSeconds) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => "expirationdate este cu mai mult de {$maxDays} zile după vdate",
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'expirationdate ok'];
    }

    return $findings;
}
