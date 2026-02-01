<?php
/**
 * TC12 – Job Model – Salary format
 *
 * Scop:
 * - Verifică faptul că, dacă `salary` este prezent, este string.
 * - Verifică formatele acceptate:
 *   - "MIN-MAX CURRENCY" (ex: "5000-8000 RON")
 *   - "VALUE CURRENCY" (ex: "4900 RON")
 *
 * Rezultat:
 * - WARN dacă `salary` lipsește (opțional, dar foarte util).
 * - FAIL dacă `salary` este prezent dar nu respectă formatul.
 * - PASS dacă `salary` este conform.
 */
function tc12_job_salary_format(array $doc, array $ctx): array {
    $findings = [];

    // "5000-8000 RON"
    $reRange  = '/^[0-9]+-[0-9]+\s+[A-Z]{3}$/';
    // "4900 RON"
    $reSingle = '/^[0-9]+\s+[A-Z]{3}$/';

    if (!array_key_exists('salary', $doc) || $doc['salary'] === null) {
        $findings[] = [
            'status'  => 'WARN',
            'message' => 'salary lipsește (opțional, dar foarte util)',
        ];
        return $findings;
    }

    if (!is_string($doc['salary'])) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => 'salary trebuie să fie string',
        ];
        return $findings;
    }

    $salary = trim($doc['salary']);

    if (!preg_match($reRange, $salary) && !preg_match($reSingle, $salary)) {
        $findings[] = [
            'status'  => 'FAIL',
            'message' => "salary nu respectă formatul 'MIN-MAX CURRENCY' sau 'VALUE CURRENCY' (ex: '5000-8000 RON', '4900 RON')",
        ];
    } else {
        $findings[] = ['status' => 'PASS', 'message' => 'salary ok'];
    }

    return $findings;
}
