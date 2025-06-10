<?php
include 'db.php';

$first = $_POST['firstName'];
$last = $_POST['lastName'];
$city = $_POST['city'];
$state = $_POST['state'];
$setting = $_POST['setting'];
$email = $_POST['email'];
$password = $_POST['password'];

$first = $conn->real_escape_string($first);
$last = $conn->real_escape_string($last);
$city = $conn->real_escape_string($city);
$state = $conn->real_escape_string($state);
$setting = $conn->real_escape_string($setting);
$email = $conn->real_escape_string($email);
$password = $conn->real_escape_string($password);

// Optional: Check for duplicates
$check = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($check->num_rows > 0) {
  echo "An account with this email already exists.";
  exit;
}

// Insert into DB
$sql = "INSERT INTO users (first_name, last_name, city, state, setting, email, password)
        VALUES ('$first', '$last', '$city', '$state', '$setting', '$email', '$password')";

if ($conn->query($sql) === TRUE) {
  echo "<h2>Account created successfully! You may now <a href='login.html'>log in</a>.</h2>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
