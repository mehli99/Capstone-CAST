<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join CAST Session</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Styles specific to the join page form */
        .join-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        #session-code-input {
            width: 100%;
            max-width: 350px;
            padding: .85rem 1rem;
            font-size: 1.2rem;
            border: 2px solid #005b86;
            border-radius: 999px;
            background: #fff;
            color: #005b86;
            text-align: center;
            text-transform: uppercase;
        }
        #session-code-input::placeholder {
            color: #005b86cc;
        }
        .join-button {
            width: 100%;
            max-width: 350px;
        }
    </style>
</head>
<body>
    <main class="dashboard-card">
        <header class="dashboard-header">
            <div class="logo-box"><img src="CAST_LOGO.png" alt="CAST logo"></div>
        </header>
        <h1 class="section-heading">Join Assessment Session</h1>
        
        <form id="join-form" class="join-box">
            <p class="instructions" style="margin-bottom: 0;">Please enter the 5-character session code provided by your clinician.</p>
            <input type="text" id="session-code-input" placeholder="ENTER CODE" maxlength="5" required>
            <button type="submit" class="menu-btn join-button">Join Session</button>
        </form>
    </main>

<script>
document.getElementById('join-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const codeInput = document.getElementById('session-code-input');
    const sessionCode = codeInput.value.trim().toUpperCase();

    if (sessionCode.length === 5) {
        // Redirect the patient to their assessment view with the entered code.
        window.location.href = `assessment_patient.php?session_code=${sessionCode}`;
    } else {
        alert('Please enter a valid 5-character session code.');
        codeInput.focus();
    }
});
</script>
</body>
</html>
