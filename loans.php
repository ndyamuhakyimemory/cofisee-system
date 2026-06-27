<?php
session_start();
include "php/db.php";

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $member_id = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $interest_rate = floatval($_POST['interest_rate'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $disbursement_date = $_POST['disbursement_date'] ?? null;
    $due_date = $_POST['due_date'] ?? null;
    
    $errors = [];
    
    if ($member_id <= 0) $errors[] = "Valid member is required";
    if ($amount <= 0) $errors[] = "Loan amount must be greater than 0";
    if ($amount > 100000000) $errors[] = "Loan amount exceeds maximum limit";
    if ($interest_rate < 0 || $interest_rate > 100) $errors[] = "Interest rate must be between 0 and 100";
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    } else {
        if ($id == 0) {
            $stmt = $conn->prepare("INSERT INTO loans (member_id, amount, interest_rate, status, disbursement_date, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iddsss", $member_id, $amount, $interest_rate, $status, $disbursement_date, $due_date);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Loan created successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error creating loan";
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE loans SET member_id = ?, amount = ?, interest_rate = ?, status = ?, disbursement_date = ?, due_date = ? WHERE id = ?");
            $stmt->bind_param("iddsss", $member_id, $amount, $interest_rate, $status, $disbursement_date, $due_date, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Loan updated successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error updating loan";
            }
            $stmt->close();
        }
    }
    
    header("Location: loans.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Loan deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting loan";
    }
    $stmt->close();
    header("Location: loans.php");
    exit();
}

// Fetch loan for editing
$edit_loan = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_loan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all loans
$result = $conn->query("SELECT l.*, m.name FROM loans l JOIN members m ON l.member_id = m.id ORDER BY l.created_at DESC");

// Fetch members for dropdown
$members = $conn->query("SELECT id, name FROM members WHERE status = 'active' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans Management - COFISEE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        nav {
            background: #f8f9fa;
            padding: 0;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }
        nav a {
            padding: 12px 18px;
            text-decoration: none;
            color: #333;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-size: 0.95em;
        }
        nav a:hover {
            background: #e9ecef;
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        .main-content {
            padding: 40px;
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .form-section, .table-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }
        button:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table thead {
            background: #667eea;
            color: white;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
        }
        table tbody tr:hover {
            background: #f0f0f0;
        }
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            font-size: 0.85em;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background: #007bff;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-defaulted {
            background: #f8d7da;
            color: #721c24;
        }
        footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Loans Management</h1>
        </header>

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
                    <h2><?= $action === 'add' ? 'Disburse New Loan' : 'Edit Loan'; ?></h2>
                    <form method="POST">
                        <?php if ($action === 'edit' && $edit_loan): ?>
                            <input type="hidden" name="id" value="<?= $edit_loan['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="id" value="0">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Member *</label>
                                <select name="member_id" required>
                                    <option value="">-- Select Member --</option>
                                    <?php 
                                    $members->data_seek(0);
                                    while ($m = $members->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $m['id']; ?>" <?= ($edit_loan['member_id'] ?? 0) == $m['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($m['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Loan Amount (UGX) *</label>
                                <input type="number" name="amount" step="0.01" required value="<?= $edit_loan['amount'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Interest Rate (%) *</label>
                                <input type="number" name="interest_rate" step="0.01" required value="<?= $edit_loan['interest_rate'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="pending" <?= ($edit_loan['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?= ($edit_loan['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="paid" <?= ($edit_loan['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="defaulted" <?= ($edit_loan['status'] ?? '') === 'defaulted' ? 'selected' : ''; ?>>Defaulted</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Disbursement Date</label>
                                <input type="date" name="disbursement_date" value="<?= $edit_loan['disbursement_date'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" value="<?= $edit_loan['due_date'] ?? ''; ?>">
                            </div>
                        </div>

                        <button type="submit"><?= $action === 'add' ? 'Disburse Loan' : 'Update Loan'; ?></button>
                        <a href="loans.php"><button type="button" class="btn-secondary">Cancel</button></a>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-section">
                <h2>Loans List</h2>
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member Name</th>
                                <th>Amount (UGX)</th>
                                <th>Interest Rate (%)</th>
                                <th>Status</th>
                                <th>Disbursed</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id']; ?></td>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= number_format($row['amount'], 2); ?></td>
                                    <td><?= $row['interest_rate']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= $row['disbursement_date'] ?? '-'; ?></td>
                                    <td><?= $row['due_date'] ?? '-'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="loans.php?action=edit&id=<?= $row['id']; ?>" class="btn-edit">Edit</a>
                                            <a href="loans.php?delete=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this loan?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No loans found. <a href="loans.php?action=add">Disburse a new loan</a></p>
                <?php endif; ?>
            </div>

            <?php if (!($action === 'add' || $action === 'edit')): ?>
                <div style="margin-top: 20px;">
                    <a href="loans.php?action=add"><button>+ Disburse New Loan</button></a>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>