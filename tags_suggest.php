<?php
require_once "db_connect.php";

$q = $_GET['q'] ?? '';
$q = "%$q%";

$stmt = mysqli_prepare($db_connect, "SELECT name FROM tags WHERE name LIKE ? LIMIT 5");
mysqli_stmt_bind_param($stmt, "s", $q);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tags = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tags[] = $row['name'];
}

header('Content-Type: application/json');
echo json_encode($tags);
