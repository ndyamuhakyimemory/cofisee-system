<?php
include "php/db.php";

$member_id = $_POST['member_id'];
$amount = $_POST['amount'];
$interest = $_POST['interest_rate'];

$conn->query("INSERT INTO loans (member_id, amount, interest_rate)
VALUES ('$member_id', '$amount', '$interest')");

header("Location: loans.php");
?>