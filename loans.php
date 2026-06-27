<?php
include "php/db.php";

$result = $conn->query("SELECT * FROM loans");
?>

<h2>Loans</h2>

<form action="add_loan.php" method="POST">
  <input name="member_id" placeholder="Member ID">
  <input name="amount" placeholder="Amount">
  <input name="interest_rate" placeholder="Interest %">
  <button>Add Loan</button>
</form>

<hr>

<?php while($row = $result->fetch_assoc()) { ?>
  <p><?= $row['amount'] ?> - <?= $row['status'] ?></p>
<?php } ?>