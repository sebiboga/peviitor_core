<?php

/**
 * TC10 – Job Model – vdate validă și corelată cu status
 *
 * Scop:
 *  - Verifică faptul că, dacă `vdate` este prezent, este ISO8601 UTC valid.
 *  - Verifică faptul că `vdate` este setat doar când `status = "verified"`.
 *
 * Rezultat:
 *  - WARN dacă `vdate` lipsește (apare doar la status=verified).
 *  - FAIL dacă `vdate` este invalidă sau status != verified când vdate este set.
 *  - PASS dacă relația vdate–status este conformă.
 */
function tc10_job_vdate_when_verified(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('vdate', $doc) || $doc['vdate'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'vdate lipsește (va exista doar când status=verified)',
        ];
        return $findings;
    }

    if (!is_iso8601_utc($doc['vdate'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'vdate nu este ISO8601 UTC valid',
        ];
    }
    if (!isset($doc['status']) || $doc['status'] !== 'verified') {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'vdate este set dar status != verified',
        ];
    }

    if (empty($findings)) {
        $findings[] = ['status' => 'PASS', 'message' => 'vdate ok'];
    }

    return $findings;
}
