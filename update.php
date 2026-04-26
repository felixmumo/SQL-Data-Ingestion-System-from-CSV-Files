<?php
$conn = new mysqli("localhost", "root", "", "gov_finance");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$year = $_POST['year'];
$month = $_POST['month'];
$revenue = $_POST['revenue'];
$expenditure = $_POST['expenditure'];

// Validate month
if ($month < 1 || $month > 12) {
    die("❌ Invalid month");
}

// Check duplicate
$check = $conn->prepare("SELECT id FROM finances WHERE year=? AND month=? AND id != ?");
$check->bind_param("iii", $year, $month, $id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    die("❌ Duplicate Entries not Allowed for $year-$month");
}

// Update
$stmt = $conn->prepare("UPDATE finances SET year=?, month=?, revenue=?, expenditure=? WHERE id=?");
$stmt->bind_param("iiddi", $year, $month, $revenue, $expenditure, $id);

if ($stmt->execute()) {
    header("Location: edit_finances.php?success=1");
} else {
    echo "❌ Error updating";
}
?>