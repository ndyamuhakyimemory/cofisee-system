<?php
session_start();
include "php/db.php";

$result = $conn->query("
    SELECT 
        l.id,
        m.id as member_id,
        m.name,
        m.phone,
        l.amount,
        l.interest_rate,
        l.status,
        l.due_date,
        DATEDIFF(NOW(), l.due_date) as days_overdue
    FROM loans l
    JOIN members m ON l.member_id = m.id
    WHERE l.status IN ('pending', 'active')
    AND l.due_date IS NOT NULL
    AND DATE_ADD(l.due_date, INTERVAL 28 DAY) < NOW()
    ORDER BY days_overdue DESC
");

$total_defaulters = $result ? $result->num_rows : 0;
$total_overdue = 0;

if ($result && $total_defaulters > 0) {
    $amount_result = $conn->query("
        SELECT SUM(l.amount) as total 
        FROM loans l
        JOIN members m ON l.member_id = m.id
        WHERE l.status IN ('pending', 'active')
        AND l.due_date IS NOT NULL
        AND DATE_ADD(l.due_date, INTERVAL 28 DAY) < NOW()
    ");
    $amount_data = $amount_result->fetch_assoc();
    $total_overdue = $amount_data['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defaulters - COFISEE</title>
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
        .alert{padding:20px;margin-bottom:30px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;}
        .stat-card{background:#f8f9fa;padding:20px;border-radius:8px;border-left:4px solid #dc3545;text-align:center;}
        .stat-card h3{font-size:0.9em;color:#666;margin-bottom:10px;text-transform:uppercase;}
        .stat-card .number{font-size:2.5em;color:#dc3545;font-weight:bold;}
        .table-section{background:#f8f9fa;padding:25px;border-radius:8px;margin-top:30px;}
        table{width:100%;border-collapse:collapse;margin-top:20px;}
        table thead{background:#dc3545;color:#fff;}
        table th,table td{padding:12px;text-align:left;}
        table tbody tr:hover{background:#f0f0f0;}
        .critical{color:#dc3545;font-weight:bold;}
        footer{background:#f8f9fa;padding:20px;text-align:center;color:#666;border-top:1px solid #e9ecef;}
        h2{color:#333;margin-bottom:20px;margin-top:30px;}
        .print-btn{background:#6c757d;color:#fff;padding:12px 30px;border:none;border-radius:5px;cursor:pointer;font-size:1em;margin-bottom:20px;}
        .print-btn:hover{background:#5a6268;}
    </style>
</head>
<body>
    <div class="container">
        <header><h1>⚠️ Defaulters Report</h1><p>Loans Overdue by 28+ Days</p></header>
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
            <?php if ($total_defaulters > 0): ?>
                <div class="alert"><strong>Alert!</strong> You have <?= $total_defaulters; ?> member(s) with overdue loans. Total at risk: UGX <?= number_format($total_overdue, 0); ?></div>
                <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
                <div class="stats-grid">
                    <div class="stat-card"><h3>Total Defaulters</h3><div class="number"><?= $total_defaulters; ?></div></div>
                    <div class="stat-card"><h3>Total Overdue Amount</h3><div class="number">UGX <?= number_format($total_overdue, 0); ?></div></div>
                </div>
                <div class="table-section">
                    <h2>Defaulters List</h2>
                    <table>
                        <thead><tr><th>Loan ID</th><th>Member Name</th><th>Phone</th><th>Amount (UGX)</th><th>Interest (%)</th><th>Due Date</th><th>Days Overdue</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php $result->data_seek(0); while ($row = $result->fetch_assoc()): $overdue_class = $row['days_overdue'] > 90 ? 'critical' : ''; ?>
                                <tr><td><?= $row['id']; ?></td><td><?= htmlspecialchars($row['name']); ?></td><td><?= htmlspecialchars($row['phone']); ?></td><td><?= number_format($row['amount'], 2); ?></td><td><?= $row['interest_rate']; ?>%</td><td><?= $row['due_date']; ?></td><td class="<?= $overdue_class; ?>"><?= $row['days_overdue']; ?> days</td><td><?= ucfirst($row['status']); ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background:#d4edda;color:#155724;padding:20px;border-radius:8px;text-align:center;"><h2>✓ No Defaulters</h2><p>Great! All loans are up to date.</p></div>
            <?php endif; ?>
        </div>
        <footer><p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p></footer>
    </div>
</body>
</html>