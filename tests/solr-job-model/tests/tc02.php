<?php

/**
 * TC02 – Job Model – Title required & format
 *
 * Scop:
 *  - Verifică faptul că fiecare document are câmpul `title` prezent (required).
 *  - Verifică faptul că `title` are maxim 200 de caractere.
 *  - Verifică faptul că `title` nu conține HTML.
 *  - Verifică faptul că `title` nu are spații la început și la sfârșit.
 *
 * Rezultat:
 *  - FAIL dacă `title` lipsește sau nu respectă regulile.
 *  - PASS dacă `title` este conform.
 */
function tc02_job_title_required(array $doc, array $ctx): array {
    $findings = [];

    if (!isset($doc['title']) || $doc['title'] === '') {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'title lipsă (câmp required)',
        ];
        return $findings;
    }

    $title   = $doc['title'];
    $trimmed = trim($title);

    if ($trimmed !== $title) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'title trebuie să fie fără whitespace la început/sfârșit',
        ];
    }
    if (mb_strlen($trimmed, 'UTF-8') > 200) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'title depășește 200 de caractere',
        ];
    }
    if ($trimmed !== strip_tags($trimmed)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'title nu trebuie să conțină HTML',
        ];
    }

    if (empty($findings)) {
        $findings[] = ['status' => 'PASS', 'message' => 'title ok'];
    }

    return $findings;
}
