<?php
$session_code = $_GET['session_code'] ?? 'NULL';

/* locate newest .webm for this session */
$recordingsDir   = __DIR__ . '/recordings/';          
$publicBasePath  = 'recordings/';                   

$pattern         = $recordingsDir . $session_code . '*.[Ww][Ee][Bb][Mm]';
$matches         = glob($pattern, GLOB_NOSORT);

$recordingHref   = null;                             
if ($matches) {
    usort($matches, fn($a, $b) => filemtime($a) <=> filemtime($b));
    $latestFile   = end($matches);             
    $recordingHref = $publicBasePath . basename($latestFile);
}

/*  session JSON  */
$session_file_path = __DIR__ . '/sessions/' . $session_code . '.json';
if (!file_exists($session_file_path)) {
    die("Error: Session not found. This summary may have expired or is invalid.");
}

$data = json_decode(file_get_contents($session_file_path), true);

/* patient profile */
$patient_profile      = $data['patient_profile'];
$patient_name_display = strtoupper($patient_profile['last_Name']) . ', ' .
                        strtoupper(substr($patient_profile['first_Name'], 0, 1));

$patient_link_params = http_build_query([
    'id'         => $patient_profile['id'],
    'name'       => $patient_name_display,
    'first_Name' => $patient_profile['first_Name'],
    'last_Name'  => $patient_profile['last_Name'],
    'dob'        => $patient_profile['dob'],
    'sex'        => $patient_profile['sex'],
    'email'      => $patient_profile['email']
]);

/*  PHP Score Calculation */
$domains = ["Language Expression", "Language Comprehension", "Fluency",
            "Cognition", "Multimodal Communication"];
$raw_scores = [];
foreach ($domains as $domain) { $raw_scores[$domain] = []; }
$overall_scores = [];

foreach ($data['scores'] as $scenario => $tasks) {
    $scenario_overall = [];
    foreach ($tasks as $task_scores) {
        foreach ($domains as $domain) { if (isset($task_scores[$domain])) $raw_scores[$domain][] = $task_scores[$domain]; }
        if (isset($task_scores['Overall'])) $scenario_overall[] = $task_scores['Overall'];
    }
    if (!empty($scenario_overall)) { $overall_scores[] = array_sum($scenario_overall) / count($scenario_overall); }
}

$raw_averages = [];
foreach ($domains as $domain) { $raw_averages[$domain] = !empty($raw_scores[$domain]) ? array_sum($raw_scores[$domain]) / count($raw_scores[$domain]) : 0; }
$overall_communication_score = !empty($overall_scores) ? array_sum($overall_scores) / count($overall_scores) : 0;
$weighted_scores = [];
$report_date = date("F j, Y"); 
$report_date_slug = date("Ymd");
foreach ($domains as $domain) { $weighted_scores[$domain] = $raw_averages[$domain] * ($overall_communication_score / 5.0) * 5; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CAST Assessment Summary</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .dashboard-card { max-width: 950px; padding-bottom: 6rem; }
        .results-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; align-items: center; margin-bottom: 2rem; }
        .notes-section { margin-top: 2rem; border-top: 2px solid #e9eef2; padding-top: 2rem; }
        .notes-textarea { width: 100%; min-height: 150px; padding: 1rem; border-radius: .5rem; border: 2px solid #005b86; font-size: 1rem; margin-bottom: 1.5rem; }
        .results-table { width: 100%; border-collapse: collapse; }
        .results-table th, .results-table td { border: 1px solid #ddd; padding: .75rem; text-align: center; }
        .results-table th { background-color: #f4f7f9; color: #005b86; font-weight: 600; }
        .results-table td:first-child { text-align: left; font-weight: 600; }
    </style>
</head>
<body>
    <main class="dashboard-card">
        <div id="pdf-content">
            <header class="dashboard-header">
                <a href="homepage.html" class="logo-box" title="Back to home"><img src="CAST_LOGO.png" alt="CAST logo"></a>
            </header>
            <h1 class="section-heading rail-wide"><span class="heading-label">CAST COMMUNICATION PROFILE: <?= htmlspecialchars(strtoupper($patient_name_display)) ?></span></h1>
            <p style="text-align:center;font-weight:600;margin:-.5rem 0 2rem;color:#666;">Assessment Date: <?= $report_date ?></p>
            <h3 style="text-align:center; color: #005b86; margin-bottom: 2rem;">Overall Communication Score: <?= number_format($overall_communication_score, 2) ?> / 5.0</h3>

            <div class="results-grid">
                <div class="chart-container"><canvas id="resultsChart"></canvas></div>
                <div class="table-container">
                    <table class="results-table">
                        <thead><tr><th>Domain</th><th>Raw Average</th><th>Weighted Score</th></tr></thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td><?= $domain ?></td>
                                <td><?= number_format($raw_averages[$domain], 2) ?></td>
                                <td><?= number_format($weighted_scores[$domain], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="notes-section">
                <h3 class="section-heading rail-short"><span class="heading-label">CLINICIAN NOTES</span></h3>
                <textarea id="clinician-notes-textarea" class="notes-textarea" placeholder="Enter clinical interpretation or intervention planning..."></textarea>
            </div>
        </div>

        <div class="menu-column" style="margin-top: 2rem;">
            <button id="download-pdf-btn" class="menu-btn">Download PDF Report</button>
			<?php if ($recordingHref): ?>
				<a href="<?= htmlspecialchars($recordingHref) ?>"
				   download
				   class="menu-btn"
				   style="margin-top:1rem;">
					Download Recording
				</a>
			<?php else: ?>
				<p style="text-align:center;margin-top:1rem;font-size:.9rem;color:#666;">
					Recording not available yet. Refresh in a few seconds.
				</p>
			<?php endif; ?>
            <!-- **FIX**: This link now uses the complete, correct query string. -->
            <a href="patientHomepage.php?<?= $patient_link_params ?>" class="back-btn" style="position: static; width: 90%; max-width: 1000px;">Return to Patient Homepage</a>
        </div>
    </main>

<script>

document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('resultsChart').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: <?= json_encode($domains) ?>,
            datasets: [{
                label: 'Weighted Score',
                data: <?= json_encode(array_values($weighted_scores)) ?>,
                fill: true, backgroundColor: 'rgba(70, 162, 214, 0.2)',
                borderColor: 'rgb(70, 162, 214)', pointBackgroundColor: 'rgb(70, 162, 214)',
                pointBorderColor: '#fff', pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(70, 162, 214)'
            }]
        },
        options: { scales: { r: { angleLines: { display: false }, suggestedMin: 0, suggestedMax: 25 } }, elements: { line: { borderWidth: 3 } } }
    });

    document.getElementById('download-pdf-btn').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const content = document.getElementById('pdf-content');
        html2canvas(content, { scale: 1 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save("CAST_Report_<?= htmlspecialchars($patient_name_display) ?>_<?= $report_date_slug ?>.pdf");
        });
    });
});
</script>
</body>
</html>
