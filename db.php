<?php
$servername= "localhost";
$username= "admin"
$password= "";
$dbname= "cast_app"

$conn =new myslqi($servername, $username, $password, $dbname);

if($conn->connect_error) {
  die("Connection Failed: " . $conn->connect_error);
}
?>
