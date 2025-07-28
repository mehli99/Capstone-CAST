<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';


$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT id, first_Name, middle_Initial, last_Name, dob, sex, email FROM patients WHERE user_id = ? ORDER BY last_name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CAST — Patient Profiles</title>
  <link rel="stylesheet" href="styles.css"></head>
  <body>
    <main class="dashboard-card"><header class="dashboard-header">
      <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo" /></div>
      <button class="avatar-btn" aria-label="Profile">
        <svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M2 21c2-4 6-6 10-6s8 2 10 6"></path></svg>
      </button>
    </header>
    <h1 class="section-heading">Patient Profiles</h1>
    <section id="profilesList">
      <?php while ($p = $result->fetch_assoc()):
      $name = strtoupper($p['last_Name']) . ',' . strtoupper(substr($p['first_Name'], 0, 1));
      // The rest of the loop builds the link correctly.
      $query = http_build_query($p + ['name' => $name]);
      $url = "patientHomepage.php?$query"; ?>
      <a class="patient-pill" href="<?= $url ?>"><?= $name ?></a>
      <?php endwhile; ?>
    </section>
</main>
</body>
</html>
