<?php
$conn = new mysqli("localhost", "root", "", "gov_finance");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all data
$result = $conn->query("SELECT * FROM finances ORDER BY year, month");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Finance Data</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #0cb413; color: white; }
        input { width: 90px; padding: 5px; }
        button { padding: 6px 10px; background: #6077eb; color: white; border: none; }
        button:hover { background: #4055c5; cursor: pointer; }
    </style>
</head>
<body>

<h2>📊 Edit Government Finance Data</h2>

<table>
<tr>
    <th>ID</th>
    <th>Year</th>
    <th>Month</th>
    <th>Revenue</th>
    <th>Expenditure</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
<form action="update.php" method="POST">
    <td><?php echo $row['id']; ?></td>

    <td>
        <input type="number" name="year" value="<?php echo $row['year']; ?>" required>
    </td>

    <td>
        <input type="number" name="month" value="<?php echo $row['month']; ?>" min="1" max="12" required>
    </td>

    <td>
        <input type="number" name="revenue" value="<?php echo $row['revenue']; ?>" required>
    </td>

    <td>
        <input type="number" name="expenditure" value="<?php echo $row['expenditure']; ?>" required>
    </td>

    <td>
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <button type="submit">Update</button>
    </td>
</form>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>