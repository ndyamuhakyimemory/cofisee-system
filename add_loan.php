<?php
session_start();
include "php/db.php";

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$interest_rate = isset($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : 0;

// Validation
$errors = [];

if ($member_id <= 0) {
    $errors[] = "Valid member ID is required";
}

if ($amount <= 0) {
    $errors[] = "Loan amount must be greater than 0";
} elseif ($amount > 100000000) {
    $errors[] = "Loan amount exceeds maximum limit";
}

if ($interest_rate < 0 || $interest_rate > 100) {
    $errors[] = "Interest rate must be between 0 and 100";
}

// Verify member exists
if (empty($errors)) {
    $memberStmt = $conn->prepare("SELECT id FROM members WHERE id = ?");
    $memberStmt->bind_param("i", $member_id);
    $memberStmt->execute();
    if ($memberStmt->get_result()->num_rows === 0) {
        $errors[] = "Member does not exist";
    }
    $memberStmt->close();
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(", ", $errors);
} else {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO loans (member_id, amount, interest_rate, status) VALUES (?, ?, ?, 'pending')");
    
    if ($stmt) {
        $stmt->bind_param("idd", $member_id, $amount, $interest_rate);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Loan disbursed successfully!";
        } else {
            $_SESSION['error'] = "Error disbursing loan. Please try again.";
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error. Please try again.";
    }
}

header("Location: loans.html");
exit();
?>