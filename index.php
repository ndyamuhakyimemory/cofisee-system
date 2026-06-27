<?php
session_start();
include "php/db.php";

// Fetch comprehensive statistics
$members_result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
$total_members = $members_result->fetch_assoc()['total'];

$loans_result = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM loans WHERE status IN ('pending', 'active')");
$loans_data = $loans_result->fetch_assoc();
$active_loans = $loans_data['total'];
$total_disbursed = $loans_data['total_amount'] ?? 0;

$repayments_result = $conn->query("SELECT SUM(amount) as total FROM repayments");
$total_repayments = $repayments_result->fetch_assoc()['total'] ?? 0;

// Defaulters (overdue by 28+ days)
$defaulters_result = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans l 
    WHERE l.status IN ('pending', 'active') 
    AND l.due_date IS NOT NULL 
    AND DATE_ADD(l.due_date, INTERVAL 28 DAY) < NOW()
");
$defaulters = $defaulters_result->fetch_assoc()['total'];

$outstanding_balance = $total_disbursed - $total_repayments;

$savings_result = $conn->query("SELECT SUM(amount) as total FROM savings WHERE transaction_type = 'deposit'");
$total_savings = $savings_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COFISEE - Microfinance Management System</title>
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
            max-width: 1400px;
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
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        header p {
            font-size: 1.1em;
            opacity: 0.9;
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
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
            min-width: 120px;
        }
        nav a:hover {
            background: #e9ecef;
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        .main-content {
            padding: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .defaulters-alert {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏦 COFISEE</h1>
            <p>Microfinance Management System</p>
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
            <?php if ($defaulters > 0): ?>
                <div class="defaulters-alert">
                    <strong>⚠️ Alert!</strong> You have <?= $defaulters; ?> defaulter(s) with loans overdue by 28+ days. 
                    <a href="defaulters.php" style="color: #721c24; text-decoration: underline; font-weight: bold;">View Details</a>
                </div>
            <?php endif; ?>

            <h2>Dashboard Overview</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Members</h3>
                    <div class="number"><?= $total_members; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Loans</h3>
                    <div class="number"><?= $active_loans; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Disbursed</h3>
                    <div class="number">UGX <?= number_format($total_disbursed, 0); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Repaid</h3>
                    <div class="number">UGX <?= number_format($total_repayments, 0); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Outstanding Balance</h3>
                    <div class="number">UGX <?= number_format($outstanding_balance, 0); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Savings</h3>
                    <div class="number">UGX <?= number_format($total_savings, 0); ?></div>
                </div>
            </div>

            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <a href="members.php?action=add" class="action-btn">+ Add Member</a>
                <a href="loans.php?action=add" class="action-btn">+ Disburse Loan</a>
                <a href="repayments.php?action=add" class="action-btn">+ Record Repayment</a>
                <a href="expenses.php?action=add" class="action-btn">+ Add Expense</a>
                <a href="savings.php?action=add" class="action-btn">+ Record Saving</a>
            </div>
        </div>

        <footer>
            <p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>
