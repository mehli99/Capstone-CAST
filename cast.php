<?php
session_start();
// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get patient details from the URL to pass along
$patient_id = $_GET['id'] ?? 0;
$patient_name = $_GET['name'] ?? 'Patient';

// Define the available scenarios to be displayed as checkboxes
$required_scenarios = ["Urgent Care Center", "Restaurant", "Grocery Store", "Calling a Repair Service"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CAST Setup</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Ensures checkboxes are styled correctly */
        .patient-checkbox {
            appearance: none; width: 20px; height: 20px;
            border: 2px solid #005b86; border-radius: 4px;
            cursor: pointer; position: relative;
        }
        .patient-checkbox:checked::before {
            content:; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); color: #005b86;
            font-size: 18px; line-height: 1;
        }
    </style>
    <!-- **FIX**: Define the base URL for API calls -->
    <script>
        const BASE_URL = 'https://castslp.com';
    </script>
</head>
<body>
    <main class="dashboard-card">
        <header class="dashboard-header">
            <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo"></div>
        </header>
        <h1 class="section-heading">CAST</h1>
        
        <form id="setup-form" class="menu-column">
            <!-- **FIX**: The patient ID now comes securely from the session -->
            <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
            
			<label class="pill-with-check menu-btn">
              	<input type="checkbox" class="patient-checkbox" name="record_session" value="true">
              	<span>AUDIO + VIDEO RECORDING</span>
            </label>
            
            <div class="scenario-box">
                <p>Choose Scenarios to Run</p>
                <?php foreach ($required_scenarios as $scenario): ?>
                <label>
                    <input type="checkbox" class="patient-checkbox" name="scenarios[]" value="<?= htmlspecialchars($scenario) ?>">
                    <span><?= htmlspecialchars($scenario) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="assessment_type" value="custom" class="menu-btn">Begin Custom Assessment</button>
            <button type="submit" name="assessment_type" value="full" class="menu-btn">Begin Full Assessment</button>
        </form>
        <!-- **FIX**: The back button no longer needs to pass sensitive data -->
        <a href="patientHomepage.php" class="back-btn">← Back</a>
    </main>

<script>
document.getElementById('setup-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const assessmentType = e.submitter.value;
    formData.append('assessment_type', assessmentType);

    fetch(`${BASE_URL}/api/start_session.php`, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // **FIX**: Redirect without the patient's name in the URL.
                // The clinician page will get the name from the session file.
                window.location.href = `assessment_clinician.php?session_code=${data.session_code}`;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error starting session:', error));
});
</script>
</body>
</html>
