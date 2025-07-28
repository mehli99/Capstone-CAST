<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $first = $conn->real_escape_string($_POST['first_name']);
  $last = $conn->real_escape_string($_POST['last_name']);
  $city = $conn->real_escape_string($_POST['city']);
  $state = $conn->real_escape_string($_POST['state']);
  $setting = $conn->real_escape_string($_POST['setting']);
  $email = $conn->real_escape_string($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 


  $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
  if ($check->num_rows > 0) {
    echo "<h2>Email already exists. <a href='index.html'>Log in</a>.</h2>";
    exit;
  }

  $sql = "INSERT INTO users (first_name, last_name, city, state, setting, email, password)
          VALUES ('$first', '$last', '$city', '$state', '$setting', '$email', '$password')";

  if ($conn->query($sql) === TRUE) {

    header("Location: index.html");
    exit();
  } else {
    echo "Signup error: " . $conn->error;
  }

  $conn->close();
}
?>
