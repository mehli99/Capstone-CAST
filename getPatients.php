<?php
require 'db.php';
session_start();

// Check for authenticated user
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["error" => "Unauthorized"]);
  exit();
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

// **FIX**: Query is now filtered by user_id
$stmt = $conn->prepare("SELECT id, firstName, middleInitial, lastName, dob, sex, email FROM patients WHERE user_id = ? ORDER BY lastName ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
  $patients[] = $row;
}

$stmt->close();
echo json_encode($patients);
?>
