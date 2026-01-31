<?php

function render_html_report(array $results, array $testPlan, array $modelLabels, int $page = 1, int $perPage = 100): string {
    // --- paginare ---
    $totalDocs  = count($results['docs']);
    $totalPages = max(1, (int)ceil($totalDocs / $perPage));

    if ($page < 1) {
        $page = 1;
    } elseif ($page > $totalPages) {
        $page = $totalPages;
    }

    $start = ($page - 1) * $perPage;
    $end   = min($start + $perPage, $totalDocs);

    $html  = "<h2>Rezumat Test Plan</h2>\n";
    $html .= "<p>Total documente testate: " . htmlspecialchars((string)$results['summary']['total_docs']) . "</p>\n";

    // Rezumat Test Plan
    $html .= "<table>\n<tr><th>TC ID</th><th>Model</th><th>Test description</th><th>Câmp</th><th>Severitate</th><th>PASS</th><th>WARN</th><th>FAIL</th></tr>\n";

    foreach ($testPlan as $tc) {
        $s = $results['summary']['per_test'][$tc['id']];
        $modelLabel = isset($modelLabels[$tc['model']]) ? $modelLabels[$tc['model']] : $tc['model'];

        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($tc['id']) . "</td>";
        $html .= "<td>" . htmlspecialchars($modelLabel) . "</td>";
        $html .= "<td>" . htmlspecialchars("{$tc['id']} – {$modelLabel}") . "</td>";
        $html .= "<td>" . htmlspecialchars($tc['field']) . "</td>";
        $html .= "<td class=\"severity-" . htmlspecialchars($tc['severity']) . "\">" . htmlspecialchars($tc['severity']) . "</td>";
        $html .= "<td class=\"PASS\">" . htmlspecialchars((string)$s['pass']) . "</td>";
        $html .= "<td class=\"WARN\">" . htmlspecialchars((string)$s['warn']) . "</td>";
        $html .= "<td class=\"FAIL\">" . htmlspecialchars((string)$s['fail']) . "</td>";
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";

    // Mapare TC ID -> config
    $tcById = [];
    foreach ($testPlan as $tc) {
        $tcById[$tc['id']] = $tc;
    }

    // Paginare – sus la detalii
    $html .= "<h2>Detaliu pe document (pagina {$page} din {$totalPages})</h2>\n";

    if ($totalPages > 1) {
        $html .= "<div class=\"pager\">";
        if ($page > 1) {
            $prev = $page - 1;
            $html .= '<a href="?page=' . $prev . '">&laquo; Previous</a>';
        } else {
            $html .= '<span class="disabled">&laquo; Previous</span>';
        }
        $html .= '<span>Pagina <strong>' . $page . '</strong> din ' . $totalPages . '</span>';
        if ($page < $totalPages) {
            $next = $page + 1;
            $html .= '<a href="?page=' . $next . '">Next &raquo;</a>';
        } else {
            $html .= '<span class="disabled">Next &raquo;</span>';
        }
        $html .= "</div>\n";
    }

    // Detaliu pe document (doar docs din pagina curentă)
    for ($docIndex = $start; $docIndex < $end; $docIndex++) {
        $doc = $results['docs'][$docIndex];

        $html .= "<h3>Document: <span class=\"doc-id\">" . htmlspecialchars($doc['id']) . "</span></h3>\n";

        // bloc JSON expandabil
        if (isset($results['raw_docs'][$docIndex])) {
            $rawDoc = $results['raw_docs'][$docIndex];
        } else {
            $rawDoc = ['_id' => $doc['id']];
        }

        // eliminăm câmpurile interne pe care nu le vrem în JSON-ul afișat
        unset($rawDoc['_root_'], $rawDoc['_version_']); 
        
        $jsonPretty = json_encode($rawDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 

        $html .= "<details class=\"json-block\">\n";
        $html .= "<summary>Vezi JSON-ul original al documentului</summary>\n";
        $html .= "<pre class=\"json\">" . htmlspecialchars($jsonPretty, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>\n";
        $html .= "</details>\n";

        // tabel TC-uri pentru document
        $html .= "<table>\n<tr><th>TC ID</th><th>Model</th><th>Câmp</th><th>Severitate</th><th>Status</th><th>Mesaje</th></tr>\n";

        foreach ($doc['tests'] as $tid => $tres) {
            $tc    = $tcById[$tid] ?? null;
            $stat  = $tres['status'];
            $msgs  = implode("<br>", array_map('htmlspecialchars', $tres['messages']));
            $model = $tc['model'] ?? '?';
            $field = $tc['field'] ?? '?';
            $sev   = $tc['severity'] ?? 'WARN';
            $modelLabel = isset($modelLabels[$model]) ? $modelLabels[$model] : $model;

            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($tid) . "</td>";
            $html .= "<td>" . htmlspecialchars($modelLabel) . "</td>";
            $html .= "<td>" . htmlspecialchars($field) . "</td>";
            $html .= "<td class=\"severity-" . htmlspecialchars($sev) . "\">" . htmlspecialchars($sev) . "</td>";
            $html .= "<td class=\"" . htmlspecialchars($stat) . "\">" . htmlspecialchars($stat) . "</td>";
            $html .= "<td>{$msgs}</td>";
            $html .= "</tr>\n";
        }

        $html .= "</table>\n";
    }

    // Paginare – jos
    if ($totalPages > 1) {
        $html .= "<div class=\"pager\">";
        if ($page > 1) {
            $prev = $page - 1;
            $html .= '<a href="?page=' . $prev . '">&laquo; Previous</a>';
        } else {
            $html .= '<span class="disabled">&laquo; Previous</span>';
        }
        $html .= '<span>Pagina <strong>' . $page . '</strong> din ' . $totalPages . '</span>';
        if ($page < $totalPages) {
            $next = $page + 1;
            $html .= '<a href="?page=' . $next . '">Next &raquo;</a>';
        } else {
            $html .= '<span class="disabled">Next &raquo;</span>';
        }
        $html .= "</div>\n";
    }

    return $html;
}
