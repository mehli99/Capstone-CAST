<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php'; 

$patient = []; 
$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $patientId = $_GET['id'];
    
    // **FIX**: The query now checks that the patient ID matches AND belongs to the logged-in user.
    $stmt = $conn->prepare("SELECT id, first_Name, middle_Initial, last_Name, dob, sex, email FROM patients WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $patientId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    } else {
        // If the patient doesn't belong to the user, treat as not found.
        echo "Patient not found or you do not have permission to view this profile.";
        exit;
    }
    $stmt->close();
}

$fields = ['first_Name','middle_Initial','last_Name','dob','sex','email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CAST — Patient Information</title><link rel="stylesheet" href="styles.css"></head>
<body>
  <main class="dashboard-card patient-card"><header class="dashboard-header">
    <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo" /></div>
    <button class="avatar-btn" aria-label="Profile">
      <svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M2 21c2-4 6-6 10-6s8 2 10 6"></path></svg>
    </button>
  </header>
  <h1 class="section-heading">Patient Info</h1>
  <form id="infoForm" autocomplete="off">
    <input type="hidden" name="id" value="<?= htmlspecialchars($patient['id'] ?? '') ?>" />

    <?php foreach ($fields as $key): ?>
    <div class="field edit-field">
      <input name="<?= $key ?>" placeholder="<?= strtoupper(str_replace('_', ' ', $key)) ?>" value="<?= htmlspecialchars($patient[$key] ?? '') ?>" />
      <button type="button" class="edit-toggle" aria-label="Edit <?= str_replace('_', ' ', $key) ?>"></button>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-accent" style="margin-top:1.75rem;">Save Changes</button>
    <div id="saveMsg" style="display: none; color: green; margin-top: 10px;">Changes saved successfully!</div>
  </form>

  <a id="backLink" class="back-btn" href="patientProfiles.php">← Back</a>
</main>
<script src="patientprofile.js"></script>
</body>
</html>