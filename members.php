<?php
include "php/db.php";

$result = $conn->query("SELECT * FROM members");
?>

<h2>Members</h2>

<form action="add_member.php" method="POST">
  <input name="name" placeholder="Name">
  <input name="phone" placeholder="Phone">
  <input name="national_id" placeholder="ID">
  <button>Add</button>
</form>

<hr>

<?php while($row = $result->fetch_assoc()) { ?>
  <p><?= $row['name'] ?> - <?= $row['phone'] ?></p>
<?php } ?>