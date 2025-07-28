<?php
$servername = "localhost:3306";
$username = "snnhfhte_castslp";
$password = "Password123!@@";
$dbname = "snnhfhte_castslp";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Don’t show error details in production
    die("Connection failed.");
}
?>