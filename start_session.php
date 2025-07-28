<?php
// api/start_session.php
// UPDATED: Now fetches and stores the full patient profile in the session file.

header('Content-Type: application/json');
require '../db.php'; // Assumes db.php is in the parent directory (htdocs)

// --- Get Patient Data ---
$patient_id = $_POST['patient_id'] ?? 0;
if (!$patient_id) {
    echo json_encode(['status' => 'error', 'message' => 'No patient ID provided.']);
    exit;
}

// Fetch full patient details from the database
$stmt = $conn->prepare("SELECT id, first_Name, last_Name, dob, sex, email FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Patient not found in database.']);
    exit;
}
$patient_data = $result->fetch_assoc();
$stmt->close();

// --- Session Creation ---
$session_code = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, 5);
$session_file_path = '../sessions/' . $session_code . '.json';
while (file_exists($session_file_path)) {
    $session_code = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, 5);
    $session_file_path = '../sessions/' . $session_code . '.json';
}

$all_scenarios = ["Urgent Care Center", "Restaurant", "Grocery Store", "Calling a Repair Service"];
$assessment_type = $_POST['assessment_type'] ?? 'custom';
$scenarios_to_run = ($assessment_type === 'full') ? $all_scenarios : ($_POST['scenarios'] ?? []);

if (empty($scenarios_to_run)) {
    echo json_encode(['status' => 'error', 'message' => 'No scenarios were selected.']);
    exit;
}

// Create the initial data structure for the session, now including full patient data.
$session_data = [
    'patient_profile' => $patient_data, // Store the full patient profile
    'scenarios_to_run' => $scenarios_to_run,
    'record_session' => isset($_POST['record_session']),
    'status' => 'waiting_for_patient',
    'current_scenario_idx' => 0,
    'current_task_idx' => -1,
    'scores' => []
];

file_put_contents($session_file_path, json_encode($session_data, JSON_PRETTY_PRINT));

// The patient name for the clinician view is now taken from the database record.
echo json_encode([
    'status' => 'success', 
    'session_code' => $session_code,
    'patient_name' => $patient_data['first_Name'] . ' ' . $patient_data['last_Name']
]);
