<?php
session_start();
include "php/db.php";

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$national_id = isset($_POST['national_id']) ? trim($_POST['national_id']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
} elseif (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = "Name must be between 2 and 100 characters";
}

if (empty($phone)) {
    $errors[] = "Phone number is required";
} elseif (!preg_match('/^[0-9+\-() ]{7,}$/', $phone)) {
    $errors[] = "Invalid phone number format";
}

if (empty($national_id)) {
    $errors[] = "National ID is required";
} elseif (strlen($national_id) < 5 || strlen($national_id) > 50) {
    $errors[] = "National ID must be between 5 and 50 characters";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(", ", $errors);
} else {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO members (name, phone, national_id) VALUES (?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sss", $name, $phone, $national_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Member registered successfully!";
        } else {
            $_SESSION['error'] = "Error registering member. Please try again.";
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error. Please try again.";
    }
}

header("Location: members.html");
exit();
?>