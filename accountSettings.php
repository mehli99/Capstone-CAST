<?php
session_start();
require 'db.php'; 


$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  
  http_response_code(401); 
  echo json_encode(['error' => 'User not authenticated']);
  exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $stmt = $conn->prepare("SELECT email, first_name, last_name, city, state, setting FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_assoc();
  

  header('Content-Type: application/json');
  echo json_encode($data);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email      = $_POST['email'] ?? '';
  $first_name = $_POST['firstName'] ?? '';
  $last_name  = $_POST['lastName'] ?? '';
  $city       = $_POST['city'] ?? '';
  $state      = $_POST['state'] ?? '';
  $setting    = $_POST['setting'] ?? '';

  
  $stmt = $conn->prepare("UPDATE users SET email=?, first_name=?, last_name=?, city=?, state=?, setting=? WHERE id=?");

  $stmt->bind_param("ssssssi", $email, $first_name, $last_name, $city, $state, $setting, $user_id);
  $stmt->execute();


  if (!empty($_POST['oldPassword']) && !empty($_POST['newPassword'])) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (password_verify($_POST['oldPassword'], $result['password'])) {
      $new_password = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
      $stmt->bind_param("si", $new_password, $user_id);
      $stmt->execute();
    }
  }

  echo "success";
  exit();
}
?>