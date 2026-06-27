<?php
include "php/db.php";
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.html");
    exit();
}

$members = $conn->query("SELECT COUNT(*) as total FROM members")->fetch_assoc();
$loans = $conn->query("SELECT COUNT(*) as total FROM loans")->fetch_assoc();
?>

<h1>COFISEE Dashboard</h1>

<div>
  <h3>Total Members: <?= $members['total'] ?></h3>
  <h3>Total Loans: <?= $loans['total'] ?></h3>
</div>

<a href="logout.php">Logout</a>