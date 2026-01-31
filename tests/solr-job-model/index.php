<?php

$statusFile = __DIR__ . '/status.json';
$reportFile = __DIR__ . '/report.html';

$status = null;
if (file_exists($statusFile)) {
    $json = file_get_contents($statusFile);
    $status = json_decode($json, true);
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solr Job Model – Test Runner</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; max-width: 1000px; }
        .status-running { color: #b8860b; font-weight: bold; }
        .status-finished { color: #006400; font-weight: bold; }
        .status-error { color: #b22222; font-weight: bold; }

        .progress-container {
            width: 100%;
            border: 1px solid #ccc;
            height: 20px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .progress-bar {
            height: 100%;
            background-color: #4caf50;
            width: 0%;
            transition: width 0.5s ease;
        }
        .progress-text {
            font-size: 12px;
            color: #555;
        }

        .refresh-btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 10px 0;
            background: #1976d2;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        .refresh-btn:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>

<h1>Solr Job Model – Test Runner</h1>

<button class="refresh-btn" onclick="location.reload();">Refresh</button>

<?php if (!$status): ?>
    <p><strong>Nu există status curent.</strong> Probabil testele nu au fost rulate încă.</p>
    <p>Pornește containerul de teste și apasă „Refresh” ca să vezi progresul.</p>

<?php else: ?>
    <?php
    $state = $status['state'] ?? 'unknown';
    $cur   = (int)($status['current_doc_index'] ?? 0);
    $total = (int)($status['total_docs'] ?? 0);
    $perc  = ($total > 0) ? floor($cur * 100 / $total) : 0;
    ?>

    <?php if ($state === 'running'): ?>
        <p class="status-running">Status: rulează testele...</p>

        <div class="progress-container">
            <div class="progress-bar" style="width: <?php echo $perc; ?>%;"></div>
        </div>
        <p class="progress-text">
            Progres: <?php echo $cur; ?> din <?php echo $total; ?> documente (<?php echo $perc; ?>%).
        </p>

    <?php elseif ($state === 'finished'): ?>
        <p class="status-finished">Status: testele s-au terminat.</p>

        <div class="progress-container">
            <div class="progress-bar" style="width: 100%;"></div>
        </div>
        <p class="progress-text">
            Progres: <?php echo $total; ?> din <?php echo $total; ?> documente (100%).
        </p>

        <?php if (file_exists($reportFile)): ?>
            <hr>
            <h2>Raport detaliat</h2>
            <?php readfile($reportFile); ?>
        <?php else: ?>
            <p>Nu există încă report.html, deși statusul este „finished”.</p>
        <?php endif; ?>

    <?php else: ?>
        <p class="status-error">
            Status necunoscut: <?php echo htmlspecialchars($state); ?>.
        </p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>
