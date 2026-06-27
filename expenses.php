<?php
session_start();
include "php/db.php";

$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    category VARCHAR(50),
    expense_date DATE NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    if (empty($description)) $errors[] = "Description is required";
    if ($amount <= 0) $errors[] = "Amount must be greater than 0";
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    } else {
        if ($id == 0) {
            $stmt = $conn->prepare("INSERT INTO expenses (description, amount, category, expense_date, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $description, $amount, $category, $expense_date, $notes);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Expense recorded successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error recording expense";
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, expense_date = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("sisssi", $description, $amount, $category, $expense_date, $notes, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Expense updated successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error updating expense";
            }
            $stmt->close();
        }
    }
    
    header("Location: expenses.php");
    exit();
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Expense deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting expense";
    }
    $stmt->close();
    header("Location: expenses.php");
    exit();
}

$edit_expense = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_expense = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");

$total_result = $conn->query("SELECT SUM(amount) as total FROM expenses");
$total_expenses = $total_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - COFISEE</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1200px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.2);overflow:hidden;}
        header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:30px;text-align:center;}
        header h1{font-size:2em;margin-bottom:5px;}
        nav{background:#f8f9fa;padding:0;border-bottom:2px solid #e9ecef;display:flex;flex-wrap:wrap;}
        nav a{padding:12px 18px;text-decoration:none;color:#333;border-bottom:3px solid transparent;transition:all 0.3s;}
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
        .stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:25px;border-radius:8px;text-align:center;margin-bottom:30px;}
        .stat-card h3{opacity:0.9;margin-bottom:10px;}
        .stat-card .number{font-size:2em;font-weight:bold;}
        footer{background:#f8f9fa;padding:20px;text-align:center;color:#666;border-top:1px solid #e9ecef;}
        h2{color:#333;margin-bottom:20px;}
    </style>
</head>
<body>
    <div class="container">
        <header><h1>Expenses Management</h1></header>
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
            <div class="stat-card"><h3>Total Expenses</h3><div class="number">UGX <?= number_format($total_expenses, 0); ?></div></div>
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="form-section">
                    <h2><?= $action === 'add' ? 'Record Expense' : 'Edit Expense'; ?></h2>
                    <form method="POST">
                        <?php if ($action === 'edit' && $edit_expense): ?>
                            <input type="hidden" name="id" value="<?= $edit_expense['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="id" value="0">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group"><label>Description *</label><input type="text" name="description" required value="<?= $edit_expense['description'] ?? ''; ?>"></div>
                            <div class="form-group"><label>Amount (UGX) *</label><input type="number" name="amount" step="0.01" required value="<?= $edit_expense['amount'] ?? ''; ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Category</label><select name="category"><option value="">-- Select --</option><option value="Salaries" <?= ($edit_expense['category'] ?? '') === 'Salaries' ? 'selected' : ''; ?>>Salaries</option><option value="Rent" <?= ($edit_expense['category'] ?? '') === 'Rent' ? 'selected' : ''; ?>>Rent</option><option value="Utilities" <?= ($edit_expense['category'] ?? '') === 'Utilities' ? 'selected' : ''; ?>>Utilities</option><option value="Other" <?= ($edit_expense['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option></select></div>
                            <div class="form-group"><label>Expense Date *</label><input type="date" name="expense_date" required value="<?= $edit_expense['expense_date'] ?? date('Y-m-d'); ?>"></div>
                        </div>
                        <div class="form-group"><label>Notes</label><textarea name="notes" rows="3"><?= $edit_expense['notes'] ?? ''; ?></textarea></div>
                        <button type="submit"><?= $action === 'add' ? 'Record Expense' : 'Update Expense'; ?></button>
                        <a href="expenses.php"><button type="button" class="btn-secondary">Cancel</button></a>
                    </form>
                </div>
            <?php endif; ?>
            <div class="table-section">
                <h2>Expenses List</h2>
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>ID</th><th>Description</th><th>Category</th><th>Amount (UGX)</th><th>Date</th><th>Notes</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr><td><?= $row['id']; ?></td><td><?= htmlspecialchars($row['description']); ?></td><td><?= htmlspecialchars($row['category'] ?? '-'); ?></td><td><?= number_format($row['amount'], 2); ?></td><td><?= $row['expense_date']; ?></td><td><?= htmlspecialchars($row['notes'] ?? '-'); ?></td><td><div class="action-buttons"><a href="expenses.php?action=edit&id=<?= $row['id']; ?>" class="btn-edit">Edit</a> <a href="expenses.php?delete=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a></div></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No expenses found.</p>
                <?php endif; ?>
            </div>
            <?php if (!($action === 'add' || $action === 'edit')): ?>
                <div style="margin-top:20px;"><a href="expenses.php?action=add"><button>+ Record Expense</button></a></div>
            <?php endif; ?>
        </div>
        <footer><p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p></footer>
    </div>
</body>
</html>