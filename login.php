<?php

session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['username'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, first_name, password FROM users WHERE email = ?");
  if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
  }

  $stmt->bind_param("s", $email);

  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
      
      $_SESSION['user_id'] = $user['id'];

      
      $_SESSION['user_name'] = $user['first_name'];
      

      header("Location: homepage.html");
      exit();

    } else {
      echo "<h2>Incorrect password. <a href='index.html'>Try again</a></h2>";
    }
  } else {
    echo "<h2>User not found. <a href='index.html'>Try again</a></h2>";
  }

  $stmt->close();
  $conn->close();
}
?>