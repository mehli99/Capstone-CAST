<?php
require 'db.php'; // Your database connection file

$patient_id = $_GET['id'] ?? 0;

// To build the back link correctly, we need all patient details
$patient_data_for_link = $_GET;
$patient_name = $_GET['name'] ?? 'Patient';

$results = [];
if ($patient_id) {
    // Fetch all assessment results for this patient, most recent first
    $stmt = $conn->prepare("SELECT * FROM assessment_results WHERE patient_id = ? ORDER BY assessment_date DESC, id DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result_set = $stmt->get_result();
    while ($row = $result_set->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAST Results — <?= htmlspecialchars($patient_name) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* **UPDATED**: Increase the overall width of the results card */
        .dashboard-card {
            max-width: 950px; 
            padding-bottom: 6rem; /* Make space for back button */
        }
        .result-card {
            border: 2px solid #005b86;
            border-radius: .5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: #fff;
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: .75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .result-header h2 {
            font-size: 1.2rem;
            color: #005b86;
            margin: 0;
        }
        .result-header .overall-score {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
            background-color: #2292c9;
            padding: .5rem 1rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .result-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            align-items: flex-start;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: .5rem;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: .75rem;
            text-align: center;
        }
        .results-table th {
            background-color: #f4f7f9;
            color: #005b86;
            font-weight: 600;
        }
        .results-table td:first-child {
            text-align: left;
            font-weight: 600;
            color: #005b86;
        }
        .notes-section, .scenarios-ran-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ccc;
        }
        .notes-section strong, .scenarios-ran-section strong {
            color: #005b86;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        .notes-section p {
            line-height: 1.6;
        }
        /* **UPDATED**: Larger text area for notes */
        .notes-textarea {
            width: 100%;
            min-height: 150px;
            padding: 1rem;
            border-radius: .5rem;
            border: 2px solid #005b86;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 768px) {
            .result-content { grid-template-columns: 1fr; }
            .result-header { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <main class="dashboard-card">
        <header class="dashboard-header">
            <a href="homepage.html" class="logo-box" title="Back to home"><img src="CAST_LOGO.png" alt="CAST logo"></a>
             <button id="avatarBtn" class="avatar-btn" aria-label="Profile">
                <svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M2 21c2-4 6-6 10-6s8 2 10 6"></path></svg>
            </button>
            <nav class="profile-menu" id="profileMenu"><ul><li><a href="account.php">Account Settings</a></li><li><a href="subscription.php">Subscription</a></li><li><a href="support.html">Contact Support</a></li><li><a href="logout.php">Logout</a></li></ul></nav>
        </header>
        <h1 class="section-heading rail-wide"><span class="heading-label">CAST COMMUNICATION PROFILE: <?= htmlspecialchars($patient_name) ?> </span></h1>

        <?php if (empty($results)): ?>
            <p style="text-align: center; color: #555;">No assessment results found for this patient.</p>
        <?php else: ?>
            <?php foreach ($results as $res): ?>
                <?php
                    $weights = [
                        $res['lang_expr_weighted'],
                        $res['lang_comp_weighted'],
                        $res['fluency_weighted'],
                        $res['cognition_weighted'],
                        $res['multimodal_weighted']
                    ];
                    $scoreJson = htmlspecialchars(json_encode($weights), ENT_QUOTES);
                     $dateSlug = date('Ymd', strtotime($res['assessment_date']));
                     $patientSlug = preg_replace('/[^A-Z0-9_]/', '',
                       strtoupper($patient_name));
                ?>
                <div class="result-card">
                    <div class="result-header">
                        <h2>Assessment: <?= date("F j, Y", strtotime($res['assessment_date'])) ?></h2>
                        <span class="overall-score">Overall Score: <?= number_format($res['overall_score'], 2) ?> / 5.0</span>
                    </div>
                    <?php $pdfFile = 'pdf/' . $res['id'] . '.pdf'; ?><?php if (file_exists($pdfFile)): ?><a href="<?= $pdfFile ?>" class="menu-btn" style="margin-bottom:1rem;" download>Download PDF</a><?php endif; ?>
                    
                    <div class="scenarios-ran-section" style="border-top: none; padding-top: 0;">
                        <strong>Scenarios Tested:</strong>
                        <p><?= htmlspecialchars($res['scenarios_ran'] ?? 'N/A') ?></p>
                    </div>
                    
                    <div class="result-content">
                        <div class="chart-container">
                            <canvas class="radar-chart"
                                    width="280" height="280"
                                    data-scores='<?= $scoreJson ?>'
                                    data-result-id='<?= $res["id"] ?>'>
                            </canvas>
                        </div>
                        <div class="table-container">
                           <table class="results-table">
                               <thead>
                                   <tr><th>Domain</th><th>Raw Score</th><th>Weighted Score</th></tr>
                               </thead>
                               <tbody>
                                   <tr><td>Language Expression</td><td><?= number_format($res['lang_expr_raw'], 2) ?></td><td><?= number_format($res['lang_expr_weighted'], 2) ?></td></tr>
                                   <tr><td>Language Comprehension</td><td><?= number_format($res['lang_comp_raw'], 2) ?></td><td><?= number_format($res['lang_comp_weighted'], 2) ?></td></tr>
                                   <tr><td>Fluency</td><td><?= number_format($res['fluency_raw'], 2) ?></td><td><?= number_format($res['fluency_weighted'], 2) ?></td></tr>
                                   <tr><td>Cognition</td><td><?= number_format($res['cognition_raw'], 2) ?></td><td><?= number_format($res['cognition_weighted'], 2) ?></td></tr>
                                   <tr><td>Multimodal Communication</td><td><?= number_format($res['multimodal_raw'], 2) ?></td><td><?= number_format($res['multimodal_weighted'], 2) ?></td></tr>
                               </tbody>
                           </table>
                        </div>
                         <button class="menu-btn download-pdf-btn"
                               data-result-id='<?= $res["id"] ?>'
                               data-patient='<?= $patientSlug ?>'
                               data-date   ='<?= $dateSlug ?>'
                               style="margin:1.25rem 0;">
                            Download PDF
                        </button>
                    </div>

                    <div class="notes-section">
                        <strong>Clinician Notes:</strong>
                        <p><?= !empty($res['clinician_notes']) ? nl2br(htmlspecialchars($res['clinician_notes'])) : 'No notes were added for this assessment.' ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- **UPDATED**: This button now links back to the specific patient's homepage -->
        <a href="patientHomepage.php?<?= http_build_query($patient_data_for_link) ?>" class="back-btn">← Back to Patient Homepage</a>
    </main>
    <script>
document.getElementById('avatarBtn')?.addEventListener('click',()=>{
    document.getElementById('profileMenu')?.classList.toggle('show');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const DOMAIN_LABELS = [
        "Language Expression",
        "Language Comprehension",
        "Fluency",
        "Cognition",
        "Multimodal Communication"
    ];

    document.querySelectorAll('.radar-chart').forEach(cv => {
        const scores = JSON.parse(cv.dataset.scores);
        new Chart(cv.getContext('2d'), {
            type:'radar',
            data:{
                labels:DOMAIN_LABELS,
                datasets:[{
                    label:'Weighted Score',
                    data:scores,
                    fill:true,
                    backgroundColor:'rgba(70,162,214,.20)',
                    borderColor  :'rgb(70,162,214)',
                    pointBackgroundColor:'rgb(70,162,214)'
                }]
            },
            options:{
                plugins:{legend:{display:false}},
                scales:{ r:{
                    suggestedMin:0,suggestedMax:25,
                    angleLines:{display:false},
                    pointLabels:{font:{size:10}, callback: l => l.split(' ')}
                }},
                elements:{line:{borderWidth:2}}
            }
        });
    });

    const { jsPDF } = window.jspdf;

    document.querySelectorAll('.download-pdf-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const card  = btn.closest('.result-card');
            const pSlug = btn.dataset.patient;
            const dSlug = btn.dataset.date;
            const fname = `CAST_Report_${pSlug}_${dSlug}.pdf`;
        
            const blob  = await cardToPdfBlob(card);
            saveBlob(blob, fname);
        });
    });

    async function cardToPdfBlob(card){
        const canvas = await html2canvas(card, { scale:1.2 });
        const pdf    = new jsPDF('p','mm','a4');
        const w   = pdf.internal.pageSize.getWidth();
        const h   = canvas.height * (w / canvas.width);
        pdf.addImage(canvas, 'PNG', 0, 0, w, h);
        return pdf.output('blob');
    }

    function saveBlob(blob, filename){
        const link = document.createElement('a');
        link.href  = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }
    document.getElementById('avatarBtn')
            ?.addEventListener('click', () =>
                 document.getElementById('profileMenu')
                         ?.classList.toggle('show'));
});
</script>
</body>
</html>
