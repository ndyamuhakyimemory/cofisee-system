<?php
session_start();
include "php/db.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    header("Location: loans.php");
    exit();
}

// Display loans
$result = $conn->query("SELECT l.id, l.member_id, m.name, l.amount, l.interest_rate, l.status, l.created_at 
                        FROM loans l 
                        JOIN members m ON l.member_id = m.id 
                        ORDER BY l.created_at DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans List - COFISEE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header role="banner">
        <h1>COFISEE Loans Report</h1>
    </header>

    <nav role="navigation" aria-label="Main navigation">
        <a href="index.html">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="members.html">Members</a>
        <a href="loans.php">Loans</a>
    </nav>

    <main id="main" class="container" role="main">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <h2>Loans Directory</h2>
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Member Name</th>
                            <th>Amount (UGX)</th>
                            <th>Interest Rate (%)</th>
                            <th>Status</th>
                            <th>Date Disbursed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']); ?></td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= number_format($row['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($row['interest_rate']); ?></td>
                                <td>
                                    <strong class="<?= $row['status'] === 'pending' ? 'alert-warning' : 'alert-success'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </strong>
                                </td>
                                <td><?= date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No loans found. <a href="loans.html">Disburse a loan</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer role="contentinfo">
        <p>&copy; 2026 COFISEE Microfinance System</p>
    </footer>
</body>
</html>