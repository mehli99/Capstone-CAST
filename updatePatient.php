<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "Unauthorized";
  exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  if (!$id) {
    http_response_code(400);
    echo "Error: Patient ID is missing.";
    exit();
  }

  $firstName      = $_POST['first_Name'] ?? '';
  $middleInitial  = $_POST['middle_Initial'] ?? '';
  $lastName       = $_POST['last_Name'] ?? '';
  $dob            = $_POST['dob'] ?? '';
  $sex            = $_POST['sex'] ?? '';
  $email          = $_POST['email'] ?? '';

  // This ensures a user can only update a patient that belongs to them.
  $stmt = $conn->prepare(
    "UPDATE patients SET first_name=?, middle_initial=?, last_name=?, dob=?, sex=?, email=? WHERE id=? AND user_id=?"
  );

  // Add the user_id to the bind_param call
  $stmt->bind_param("ssssssii", $firstName, $middleInitial, $lastName, $dob, $sex, $email, $id, $user_id);

  if ($stmt->execute()) {
    // Check if any row was actually updated. If not, it means the patient didn't belong to the user.
    if ($stmt->affected_rows > 0) {
        echo "success";
    } else {
        http_response_code(403); // Forbidden
        echo "Error: You do not have permission to update this patient.";
    }
  } else {
    http_response_code(500);
    echo "Error: Could not update patient information. " . $stmt->error;
  }

  $stmt->close();
  $conn->close();
  exit();
}
?>
