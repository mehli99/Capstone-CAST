<?php
$data = $_GET;
$name = $data['name'] ?? 'Patient';
$params = http_build_query($data);
?>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CAST — Patient Dashboard</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="dashboard-card"><header class="dashboard-header">
    <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo"></div>
	<button class="avatar-btn" aria-label="Profile">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
    </button>
    <button class="avatar-btn" aria-label="Profile">
        <svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M2 21c2-4 6-6 10-6s8 2 10 6"></path></svg>
    </button>
</header>
<h1 class="section-heading"><?= htmlspecialchars($name) ?></h1>
<nav class="menu">
    <a href="cast.php?<?= $params ?>" class="menu-btn add-btn"><span class="plus">+</span>Start CAST</a>
    <a href="patientInfo.php?<?= $params ?>" class="menu-btn">Patient Information</a>
    <a href="results.php?<?= $params ?>" class="menu-btn">CAST Results</a>
    <a class="menu-btn"
	   href="patientRecordings.php?<?= http_build_query($_GET) ?>">
	   Video/Audio Recording
	</a>
</nav>
<a href="patientProfiles.php" class="back-btn">← Back</a>
<script src="scripts.js"></script>
</main>
</body>
</html>