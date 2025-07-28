<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// api/get_state.php
// UPDATED: Now handles the patient's first connection to update the session status.

header('Content-Type: application/json');

$session_code = $_GET['session_code'] ?? '';
$role = $_GET['role'] ?? 'clinician'; // Default to clinician if role isn't specified

if (empty($session_code) || !preg_match('/^[A-Z0-9]{5}$/', $session_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session code.']);
    exit;
}

$session_file_path = '../sessions/' . $session_code . '.json';

if (!file_exists($session_file_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Session not found.']);
    exit;
}

// Read the current state from the session file.
$session_data = json_decode(file_get_contents($session_file_path), true);

// If this request is from the patient and the session is waiting, update the status.
if ($role === 'patient' && $session_data['status'] === 'waiting_for_patient') {
    $session_data['status'] = 'ready_to_start'; // New status indicating patient has joined
    // Save the updated state back to the file.
    file_put_contents($session_file_path, json_encode($session_data, JSON_PRETTY_PRINT));
}

// Return the (potentially updated) session data.
echo json_encode($session_data);
