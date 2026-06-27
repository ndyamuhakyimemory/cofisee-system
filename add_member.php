<?php
include "php/db.php";

$name = $_POST['name'];
$phone = $_POST['phone'];
$nid = $_POST['national_id'];

$conn->query("INSERT INTO members (name, phone, national_id)
VALUES ('$name', '$phone', '$nid')");

header("Location: members.php");
?>