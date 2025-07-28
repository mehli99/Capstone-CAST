<?php
// assessment_clinician.php
// This is the main view for the clinician during an assessment.

$session_code = $_GET['session_code'] ?? 'NULL';
$patient_name = $_GET['patient_name'] ?? 'Patient';
// Define the domains for the scoring grid.
$domains = ["Language Expression", "Language Comprehension", "Fluency", "Cognition", "Multimodal Communication"];
$recordingsDir = 'recordings/';
$pattern       = $recordingsDir . $session_code . '_*.webm';
$latest        = glob($pattern, GLOB_NOSORT);
$recording     = $latest ? end($latest) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinician View - CAST</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Styles for the scoring grid, adapted from the Python version's design */
        .scoring-grid { display: grid; gap: 1.5rem; margin: 2rem 0; }
        .score-item { display: grid; grid-template-columns: 220px 1fr 50px; align-items: center; gap: 1rem; }
        .score-item.overall { font-weight: bold; }
        .scale-wrapper { display: flex; align-items: center; gap: 0.5rem; }
        .score-slider { width: 100%; }
        .score-value { font-family: 'Courier New', Courier, monospace; font-size: 1.2rem; font-weight: bold; }
        .status-box { padding: .8rem; border-radius: .5rem; text-align: center; margin-bottom: 1.5rem; font-weight: 600; }
        .status-box.waiting { background: #fff3cd; color: #856404; }
        .status-box.success { background: #d4edda; color: #155724; }
        .status-box.error { background: #f8d7da; color: #721c24; }
    </style>
    <!-- **FIX**: Define the base URL for API calls -->
    <script>
        const BASE_URL = 'https://castslp.com';
    </script>
</head>
<body>
    <main class="dashboard-card">
        <header class="dashboard-header">
        <a href="homepage.html" class="logo-box" title="Back to Home"><img src="CAST_LOGO.png" alt="CAST logo"></a>
        </header>
        <div class="session-info">
            <span>Patient: <strong><?= htmlspecialchars($patient_name) ?></strong></span>
            <span>Session Code: <strong class="code"><?= htmlspecialchars($session_code) ?></strong></span>
        </div>
        <div id="status" class="status-box waiting">Waiting for patient to join...</div>
        
        <div id="assessment-area" style="display: none;">
            <h2 id="scenario-title" class="scenario-heading"></h2>
            <p  id="task-text"   class="scenario-desc"></p>

            <!-- HTML for the scoring grid -->
            <div class="scoring-grid">
                <?php foreach ($domains as $index => $domain): ?>
                <div class="score-item">
                    <label for="scale-<?= $index ?>"><?= $domain ?></label>
                    <div class="scale-wrapper"><span>1</span><input type="range" id="scale-<?= $index ?>" class="score-slider" min="1" max="5" step="0.5" value="3" data-domain="<?= htmlspecialchars($domain) ?>"><span>5</span></div>
                    <span class="score-value" id="value-<?= $index ?>">3.0</span>
                </div>
                <?php endforeach; ?>
                <div class="score-item overall">
                    <label for="scale-overall">Overall Communication Success</label>
                    <div class="scale-wrapper"><span>1</span><input type="range" id="scale-overall" class="score-slider" min="1" max="5" step="0.5" value="3" data-domain="Overall"><span>5</span></div>
                    <span class="score-value" id="value-overall">3.0</span>
                </div>
            </div>

            <button id="next-task-btn" class="menu-btn" disabled>Start Assessment</button>
        </div>
    </main>
<?php if ($recording): ?>
    <a href="<?= htmlspecialchars($recording) ?>"
       download
       class="btn download-btn">⏬ Download session recording</a>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sessionCode = '<?= htmlspecialchars($session_code) ?>';
    const statusBox = document.getElementById('status');
    const nextTaskBtn = document.getElementById('next-task-btn');
    const assessmentArea = document.getElementById('assessment-area');
    const scenarioTitleEl = document.getElementById('scenario-title');
    const taskTextEl = document.getElementById('task-text');

    // This function is now the single source for updating the UI based on a state object.
    function processStateUpdate(data) {
        if (!data || !data.status) {
            console.error("Invalid state data received:", data);
            return;
        }

        if (data.status === 'ready_to_start') {
            statusBox.textContent = '✅ Patient has joined. You can start the assessment.';
            statusBox.className = 'status-box success';
            assessmentArea.style.display = 'block';
            nextTaskBtn.disabled = false;
        } else if (data.status === 'in_progress') {
            statusBox.style.display = 'none';
            assessmentArea.style.display = 'block';
            nextTaskBtn.disabled = false;
            nextTaskBtn.textContent = 'Next Task →';
            
            // Use the text directly from the API response.
            if (data.current_scenario_text && data.current_task_text) {
                scenarioTitleEl.textContent = `Scenario ${data.current_scenario_idx + 1}: ${data.current_scenario_text}`;
                taskTextEl.textContent = `Task ${data.current_task_idx + 1}: ${data.current_task_text}`;
            }
        } else if (data.status === 'finished') {
            clearInterval(pollInterval);
            window.location.href = `assessment_summary.php?session_code=${sessionCode}`;
        }
    }

    // This function is now only for background polling.
    function getState() {
        // **FIX**: Use the full URL for the API call.
        fetch(`${BASE_URL}/api/get_state.php?session_code=${sessionCode}`)
            .then(response => response.json())
            .then(data => processStateUpdate(data))
            .catch(error => {
                console.error("Polling Error:", error);
                statusBox.textContent = "Error connecting to server. Auto-refresh stopped.";
                statusBox.className = 'status-box error';
                clearInterval(pollInterval);
            });
    }

    const pollInterval = setInterval(getState, 3000);

    function resetSliders() {
        document.querySelectorAll('.score-slider').forEach(slider => {
            slider.value = 3;
            // Manually trigger the 'input' event to update the text display.
            slider.dispatchEvent(new Event('input'));
        });
    }

    nextTaskBtn.addEventListener('click', () => {
        nextTaskBtn.disabled = true; // Prevent double-clicking
        const formData = new FormData();
        formData.append('session_code', sessionCode);
        
        const scores = {};
        document.querySelectorAll('.score-slider').forEach(slider => {
            scores[slider.dataset.domain] = parseFloat(slider.value);
        });
        formData.append('scores', JSON.stringify(scores));
        
     // **FIX**: Use the full URL for the API call.
     fetch(`${BASE_URL}/api/next_task.php`, { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok.');
                return response.json();
            })
            .then(data => {
            
                if (data.status === 'success' && data.new_state) {
                    resetSliders();
                    processStateUpdate(data.new_state);
                } else {
                    alert('Error: ' + (data.message || 'An unknown error occurred.'));
                    nextTaskBtn.disabled = false; 
                }
            })
            .catch(error => {
                console.error("Next Task Error:", error);
                alert("A network error occurred. The button has been re-enabled. Please try again.");
                nextTaskBtn.disabled = false; 
            });
    });
    // Add listeners to update score value displays on slider change
    document.querySelectorAll('.score-slider').forEach(slider => {
        slider.addEventListener('input', (e) => {
            const valueDisplay = e.target.closest('.score-item').querySelector('.score-value');
            if (valueDisplay) {
                valueDisplay.textContent = parseFloat(e.target.value).toFixed(1);
            }
        });
    });

    getState(); // Initial call to check status when the page loads.
});
</script>
</body>
</html>
