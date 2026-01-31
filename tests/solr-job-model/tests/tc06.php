<?php

/**
 * TC06 – Job Model – Tags structură și conținut
 *
 * Scop:
 *  - Verifică faptul că `tags`, dacă există, este array.
 *  - Verifică maxim 20 de intrări.
 *  - Verifică faptul că fiecare tag este lowercase.
 *  - Verifică faptul că tag-urile NU conțin diacritice românești.
 *
 * Rezultat:
 *  - WARN dacă `tags` lipsesc (opțional, dar utile).
 *  - FAIL dacă structura sau conținutul nu respectă regulile.
 *  - PASS dacă `tags` este conform.
 */
function tc06_job_tags_structure(array $doc, array $ctx): array {
    $findings = [];

    if (!array_key_exists('tags', $doc) || $doc['tags'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'tags lipsesc (opțional, dar utile)',
        ];
        return $findings;
    }

    if (!is_array($doc['tags'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'tags trebuie să fie array',
        ];
        return $findings;
    }

    if (count($doc['tags']) > 20) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'tags are mai mult de 20 de intrări',
        ];
    }

    foreach ($doc['tags'] as $t) {
        if (!is_string($t)) {
            $findings[] = [
                'status'  => 'FAIL',
                'message' => 'fiecare tag trebuie să fie string',
            ];
            continue;
        }
        if ($t !== mb_strtolower($t, 'UTF-8')) {
            $findings[] = [
                'status'  => 'FAIL',
                'message' => "tag '$t' trebuie să fie lowercase",
            ];
        }
        if (preg_match('/[ăâîșțĂÂÎȘȚ]/u', $t)) {
            $findings[] = [
                'status'  => 'FAIL',
                'message' => "tag '$t' nu trebuie să conțină diacritice",
            ];
        }
    }

    if (empty($findings)) {
        $findings[] = ['status' => 'PASS', 'message' => 'tags ok'];
    }

    return $findings;
}
