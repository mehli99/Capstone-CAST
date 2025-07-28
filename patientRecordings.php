<?php
/* ───────────  CONFIG  ─────────── */
$sessionsDir   = __DIR__ . '/sessions/';
$recordingsDir = __DIR__ . '/recordings/';
$publicRecPath = 'recordings/';           // href prefix
/* ──────────────────────────────── */

/* patient info from query string */
$patient_id   = $_GET['id']          ?? null;
$first_Name   = $_GET['first_Name']  ?? '';
$last_Name    = $_GET['last_Name']   ?? '';

if (!$patient_id) {
    die('Missing patient id.');
}

/*  gather sessions */
$entries = [];   // each: [time, score, href]

foreach (glob($sessionsDir . '*.json') as $jsonFile) {

    $data = json_decode(file_get_contents($jsonFile), true);
    $p    = $data['patient_profile'] ?? [];

    /* match this patient by ID (string-compare so 42 == "42") */
    if ((string)($p['id'] ?? '') !== (string)$patient_id) {
        continue;
    }

    /* meta */
    $sessionCode = pathinfo($jsonFile, PATHINFO_FILENAME);
    $created     = filemtime($jsonFile);

    /* overall score (pre-saved or computed) */
    $overall = $data['overall_communication_score']
            ?? calcOverall($data['scores'] ?? []);

    /* locate recording */
    $recPattern = $recordingsDir . $sessionCode . '*.{webm,WEBM}';
    $matches    = glob($recPattern, GLOB_BRACE);
    if (!$matches) continue;                      // skip if no video

    usort($matches, fn($a,$b)=>filemtime($a)<=>filemtime($b));
    $recHref = $publicRecPath . basename(end($matches));

    $entries[] = ['time'=>$created, 'score'=>$overall, 'href'=>$recHref];
}

if (!$entries) {
    die('No recordings found for this patient.');
}

/* newest to oldest */
usort($entries, fn($a,$b)=> $b['time'] <=> $a['time']);

/* ---------- helpers ---------- */
function calcOverall($scores): ?float {
    if (!$scores) return null;
    $o = [];
    foreach ($scores as $scn) foreach ($scn as $t)
        if (isset($t['Overall'])) $o[] = $t['Overall'];
    return $o ? array_sum($o)/count($o) : null;
}
function niceDate($t): string { return date('Y-m-d', $t); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CAST — <?= htmlspecialchars("$last_Name, $first_Name") ?> Recordings</title>
<link rel="stylesheet" href="styles.css">
<style>
.recording-card{padding:1.5rem;border:2px solid #e9eef2;border-radius:.75rem;margin-bottom:2rem;background:#fff;}
.recording-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;font-weight:600;}
.recording-header span{color:#005b86;}
video{width:100%;max-height:360px;border-radius:.5rem;background:#000;margin-bottom:.75rem;}
.download-btn{display:inline-block;padding:.6rem 1.25rem;border-radius:2rem;background:linear-gradient(90deg,#057eb9,#0b599e);color:#fff;font-weight:600;text-decoration:none;}
.download-btn:hover{opacity:.9;}
</style>
</head>
<body>
<main class="dashboard-card">
    <header class="dashboard-header">
        <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo"></div>
    </header>

    <h2 class="section-heading" style="margin-bottom:2rem;">
        Recordings — <?= htmlspecialchars(strtoupper("$last_Name, $first_Name")) ?>
    </h2>

    <?php foreach ($entries as $e): ?>
        <div class="recording-card">
            <div class="recording-header">
                <span><?= niceDate($e['time']) ?></span>
                <?php if ($e['score'] !== null): ?>
                    <span>Overall: <?= number_format($e['score'],2) ?> / 5</span>
                <?php endif; ?>
            </div>

            <video src="<?= htmlspecialchars($e['href']) ?>" controls muted playsinline></video>

            <a class="download-btn" href="<?= htmlspecialchars($e['href']) ?>" download>
                Download
            </a>
        </div>
    <?php endforeach; ?>

    <a href="patientHomepage.php?<?= http_build_query($_GET) ?>"
       class="back-btn" style="margin-top:2rem;">← Back</a>
</main>
</body>
</html>
