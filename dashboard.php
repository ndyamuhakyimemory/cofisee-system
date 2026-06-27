<?php
session_start();
include "php/db.php";

// Fetch statistics
$members_result = $conn->query("SELECT COUNT(*) as total FROM members");
$members_data = $members_result->fetch_assoc();
$total_members = $members_data['total'];

$loans_result = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status = 'pending'");
$loans_data = $loans_result->fetch_assoc();
$active_loans = $loans_data['total'];

$savings_result = $conn->query("SELECT SUM(amount) as total FROM loans");
$savings_data = $savings_result->fetch_assoc();
$total_savings = $savings_data['total'] ?? 0;

$balance_result = $conn->query("SELECT SUM(amount) as total FROM loans WHERE status = 'pending'");
$balance_data = $balance_result->fetch_assoc();
$outstanding_balance = $balance_data['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="COFISEE Microfinance System Dashboard">
    <title>Dashboard - COFISEE Microfinance System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>

    <header role="banner">
        <h1>COFISEE Microfinance System</h1>
        <p>Management Dashboard</p>
    </header>

    <nav role="navigation" aria-label="Main navigation">
        <a href="index.html">Home</a>
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="members.html">Members</a>
        <a href="loans.php">Loans</a>
        <a href="logout.php">Logout</a>
    </nav>

    <main id="main" class="container" role="main">
        <!-- Statistics Cards -->
        <div class="dashboard">
            <div class="dashboard-card">
                <h3>Total Members</h3>
                <p><?= $total_members; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Active Loans</h3>
                <p><?= $active_loans; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Disbursed</h3>
                <p>UGX <?= number_format($total_savings, 0); ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Outstanding Balance</h3>
                <p>UGX <?= number_format($outstanding_balance, 0); ?></p>
            </div>
        </div>
    </main>

    <footer role="contentinfo">
        <p>&copy; 2026 COFISEE Microfinance System. All rights reserved.</p>
    </footer>
</body>
</html>