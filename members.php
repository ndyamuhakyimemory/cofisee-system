<?php
session_start();
include "php/db.php";

// Display members
$result = $conn->query("SELECT id, name, phone, national_id, created_at FROM members ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members List - COFISEE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header role="banner">
        <h1>COFISEE Members List</h1>
    </header>

    <nav role="navigation" aria-label="Main navigation">
        <a href="index.html">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="members.html">Add Member</a>
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
            <h2>Members Directory</h2>
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>National ID</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']); ?></td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= htmlspecialchars($row['phone']); ?></td>
                                <td><?= htmlspecialchars($row['national_id']); ?></td>
                                <td><?= date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No members found. <a href="members.html">Register a member</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer role="contentinfo">
        <p>&copy; 2026 COFISEE Microfinance System</p>
    </footer>
</body>
</html>