<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); 
    echo "Error: You must be logged in to add a new patient.";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['first_Name'] ?? '';
    $middleInitial = $_POST['middle_Initial'] ?? '';
    $lastName = $_POST['last_Name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $email = $_POST['email'] ?? '';

    
    $sql = "INSERT INTO patients (user_id, first_name, middle_initial, last_name, dob, sex, email) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing the statement: " . $conn->error);
    }
    

    $stmt->bind_param("issssss", $user_id, $firstName, $middleInitial, $lastName, $dob, $sex, $email);

    if ($stmt->execute()) {
 
        header("Location: patientProfiles.php");
        exit();
    } else {
        echo "Error saving patient data: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>