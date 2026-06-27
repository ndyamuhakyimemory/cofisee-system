<?php
session_start();
include "php/db.php";

// If requested with GET, show a minimal form so visiting the URL doesn't return 405.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // A simple debug-friendly form — this does not replace your main loan page.
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Submit Loan (debug)</title></head><body>';
    echo '<h1>Submit Loan (debug form)</h1>';
    if (!empty($_SESSION["error"])) {
        echo '<div style="color:red;">' . htmlspecialchars($_SESSION["error"]) . '</div>';
        unset($_SESSION["error"]);
    }
    if (!empty($_SESSION["success"])) {
        echo '<div style="color:green;">' . htmlspecialchars($_SESSION["success"]) . '</div>';
        unset($_SESSION["success"]);
    }
    echo '<form method="post" action="add_loan.php">';
    echo '<label>Member ID: <input type="number" name="member_id" required></label><br><br>';
    echo '<label>Amount: <input name="amount" type="number" step="0.01" required></label><br><br>';
    echo '<label>Interest rate: <input name="interest_rate" type="number" step="0.01" required></label><br><br>';
    echo '<button type="submit">Submit loan</button>';
    echo '</form>';
    echo '</body></html>';
    exit();
}

// Only process POST requests for the insert logic
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
    if ($memberStmt) {
        $memberStmt->bind_param("i", $member_id);
        $memberStmt->execute();
        $res = $memberStmt->get_result();
        if (!$res || $res->num_rows === 0) {
            $errors[] = "Member does not exist";
        }
        $memberStmt->close();
    } else {
        $errors[] = "Database error when verifying member";
    }
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
            // Capture DB error for debugging but don't expose raw SQL errors to users in production
            $_SESSION['error'] = "Error disbursing loan. Please try again.";
            error_log('add_loan.php execute error: ' . $stmt->error);
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error. Please try again.";
        error_log('add_loan.php prepare error: ' . $conn->error);
    }
}

// Redirect back to the loan page. The repository has loan.html, so redirect there.
header("Location: loan.html");
exit();
?>