<?php
include 'db.php';

$username = $_POST['username'];
$password = $_POST['password'];

$username = $conn->real_escape_string($username);
$password = $conn->real_escape_string($password);

// Use prepared statements in production
$sql = "SELECT * FROM users WHERE email='$username' AND password='$password'";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
  echo "<h2>Welcome, $username! Login successful.</h2>";
} else {
  echo "<h2>Login failed. Invalid username or password.</h2>";
}

$conn->close();
?>
