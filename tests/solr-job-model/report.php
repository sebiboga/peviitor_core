<?php

function render_html_report(array $results, array $testPlan, array $modelLabels): string {
    $html  = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"utf-8\">\n";
    $html .= "<title>Solr Data Quality Test Report</title>\n";
    $html .= "<style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; }
        th { background: #f0f0f0; }
        .PASS { color: #006400; font-weight: bold; }
        .FAIL { color: #b22222; font-weight: bold; }
        .WARN { color: #b8860b; font-weight: bold; }
        .severity-ERROR { font-weight: bold; color: #b22222; }
        .severity-WARN  { font-weight: bold; color: #b8860b; }
        .doc-id { font-size: 12px; word-break: break-all; }
    </style>\n</head>\n<body>\n";

    $html .= "<h1>Solr Data Quality Test Report</h1>\n";
    $html .= "<p>Total documente testate: " . htmlspecialchars((string)$results['summary']['total_docs']) . "</p>\n";

    $html .= "<h2>Rezumat Test Plan</h2>\n";
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

    $html .= "<h2>Detaliu pe document</h2>\n";

    foreach ($results['docs'] as $doc) {
        $html .= "<h3>Document: <span class=\"doc-id\">" . htmlspecialchars($doc['id']) . "</span></h3>\n";
        $html .= "<table>\n<tr><th>TC ID</th><th>Model</th><th>Câmp</th><th>Severitate</th><th>Status</th><th>Mesaje</th></tr>\n";

        foreach ($testPlan as $tc) {
            $tid   = $tc['id'];
            $tres  = $doc['tests'][$tid];
            $stat  = $tres['status'];
            $msgs  = implode("<br>", array_map('htmlspecialchars', $tres['messages']));
            $modelLabel = isset($modelLabels[$tc['model']]) ? $modelLabels[$tc['model']] : $tc['model'];

            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($tid) . "</td>";
            $html .= "<td>" . htmlspecialchars($modelLabel) . "</td>";
            $html .= "<td>" . htmlspecialchars($tc['field']) . "</td>";
            $html .= "<td class=\"severity-" . htmlspecialchars($tc['severity']) . "\">" . htmlspecialchars($tc['severity']) . "</td>";
            $html .= "<td class=\"" . htmlspecialchars($stat) . "\">" . htmlspecialchars($stat) . "</td>";
            $html .= "<td>{$msgs}</td>";
            $html .= "</tr>\n";
        }

        $html .= "</table>\n";
    }

    $html .= "</body>\n</html>\n";

    return $html;
}
