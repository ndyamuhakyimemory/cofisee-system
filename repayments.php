<?php
session_start();
include "php/db.php";

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $loan_id = intval($_POST['loan_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $repayment_date = $_POST['repayment_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    
    if ($loan_id <= 0) $errors[] = "Valid loan is required";
    if ($amount <= 0) $errors[] = "Repayment amount must be greater than 0";
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    } else {
        $loan_stmt = $conn->prepare("SELECT member_id FROM loans WHERE id = ?");
        $loan_stmt->bind_param("i", $loan_id);
        $loan_stmt->execute();
        $loan_result = $loan_stmt->get_result()->fetch_assoc();
        $member_id = $loan_result['member_id'];
        $loan_stmt->close();
        
        if ($id == 0) {
            $stmt = $conn->prepare("INSERT INTO repayments (loan_id, member_id, amount, repayment_date, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $loan_id, $member_id, $amount, $repayment_date, $notes);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Repayment recorded successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error recording repayment";
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE repayments SET loan_id = ?, amount = ?, repayment_date = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("iissi", $loan_id, $amount, $repayment_date, $notes, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Repayment updated successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error updating repayment";
            }
            $stmt->close();
        }
    }
    
    header("Location: repayments.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM repayments WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Repayment deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting repayment";
    }
    $stmt->close();
    header("Location: repayments.php");
    exit();
}

$edit_repayment = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM repayments WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_repayment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT r.*, l.id as loan_id, m.name FROM repayments r JOIN loans l ON r.loan_id = l.id JOIN members m ON r.member_id = m.id ORDER BY r.repayment_date DESC");

$loans = $conn->query("SELECT l.id, CONCAT(m.name, ' - ', l.amount, ' UGX') as label FROM loans l JOIN members m ON l.member_id = m.id WHERE l.status IN ('active', 'pending') ORDER BY m.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repayments - COFISEE</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1200px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.2);overflow:hidden;}
        header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:30px;text-align:center;}
        header h1{font-size:2em;margin-bottom:5px;}
        nav{background:#f8f9fa;padding:0;border-bottom:2px solid #e9ecef;display:flex;flex-wrap:wrap;gap:0;}
        nav a{padding:12px 18px;text-decoration:none;color:#333;border-bottom:3px solid transparent;transition:all 0.3s;font-size:0.95em;}
        nav a:hover{background:#e9ecef;color:#667eea;border-bottom:3px solid #667eea;}
        .main-content{padding:40px;}
        .alert{padding:15px 20px;margin-bottom:20px;border-radius:5px;}
        .alert-success{background:#d4edda;color:#155724;}
        .alert-error{background:#f8d7da;color:#721c24;}
        .form-section,.table-section{background:#f8f9fa;padding:25px;border-radius:8px;margin-bottom:30px;}
        .form-group{margin-bottom:15px;}
        label{display:block;margin-bottom:5px;font-weight:500;color:#333;}
        input,select,textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;font-family:inherit;}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#667eea;box-shadow:0 0 5px rgba(102,126,234,0.3);}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
        button{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:12px 30px;border:none;border-radius:5px;cursor:pointer;font-size:1em;transition:all 0.3s;}
        button:hover{transform:scale(1.02);box-shadow:0 5px 15px rgba(0,0,0,0.2);}
        .btn-secondary{background:#6c757d;margin-left:10px;}
        table{width:100%;border-collapse:collapse;margin-top:20px;}
        table thead{background:#667eea;color:#fff;}
        table th,table td{padding:12px;text-align:left;}
        table tbody tr:hover{background:#f0f0f0;}
        table tbody tr:nth-child(even){background:#f9f9f9;}
        .action-buttons{display:flex;gap:10px;}
        .btn-edit,.btn-delete{padding:6px 12px;font-size:0.85em;border-radius:4px;cursor:pointer;border:none;text-decoration:none;display:inline-block;}
        .btn-edit{background:#007bff;color:#fff;}
        .btn-delete{background:#dc3545;color:#fff;}
        footer{background:#f8f9fa;padding:20px;text-align:center;color:#666;border-top:1px solid #e9ecef;}
        h2{color:#333;margin-bottom:20px;}
    </style>
</head>
<body>
    <div class="container">
        <header><h1>Repayments Management</h1></header>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="loans.php">Loans</a>
            <a href="repayments.php">Repayments</a>
            <a href="defaulters.php">Defaulters</a>
            <a href="expenses.php">Expenses</a>
            <a href="savings.php">Savings</a>
            <a href="logout.php">Logout</a>
        </nav>
        <div class="main-content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="form-section">
                    <h2><?= $action === 'add' ? 'Record Repayment' : 'Edit Repayment'; ?></h2>
                    <form method="POST">
                        <?php if ($action === 'edit' && $edit_repayment): ?>
                            <input type="hidden" name="id" value="<?= $edit_repayment['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="id" value="0">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Loan *</label>
                                <select name="loan_id" required>
                                    <option value="">-- Select Loan --</option>
                                    <?php $loans->data_seek(0); while ($l = $loans->fetch_assoc()): ?>
                                        <option value="<?= $l['id']; ?>" <?= ($edit_repayment['loan_id'] ?? 0) == $l['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($l['label']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (UGX) *</label>
                                <input type="number" name="amount" step="0.01" required value="<?= $edit_repayment['amount'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Repayment Date *</label>
                                <input type="date" name="repayment_date" required value="<?= $edit_repayment['repayment_date'] ?? date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="3"><?= $edit_repayment['notes'] ?? ''; ?></textarea>
                        </div>
                        <button type="submit"><?= $action === 'add' ? 'Record Repayment' : 'Update Repayment'; ?></button>
                        <a href="repayments.php"><button type="button" class="btn-secondary">Cancel</button></a>
                    </form>
                </div>
            <?php endif; ?>
            <div class="table-section">
                <h2>Repayments List</h2>
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>ID</th><th>Member</th><th>Loan ID</th><th>Amount (UGX)</th><th>Date</th><th>Notes</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr><td><?= $row['id']; ?></td><td><?= htmlspecialchars($row['name']); ?></td><td><?= $row['loan_id']; ?></td><td><?= number_format($row['amount'], 2); ?></td><td><?= $row['repayment_date']; ?></td><td><?= htmlspecialchars($row['notes'] ?? '-'); ?></td><td><div class="action-buttons"><a href="repayments.php?action=edit&id=<?= $row['id']; ?>" class="btn-edit">Edit</a> <a href="repayments.php?delete=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a></div></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No repayments found. <a href="repayments.php?action=add">Record a repayment</a></p>
                <?php endif; ?>
            </div>
            <?php if (!($action === 'add' || $action === 'edit')): ?>
                <div style="margin-top:20px;"><a href="repayments.php?action=add"><button>+ Record Repayment</button></a></div>
            <?php endif; ?>
        </div>
        <footer><p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p></footer>
    </div>
</body>
</html>