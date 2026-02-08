<?php
session_start();
require_once "db_connect.php";

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] < ROLE_MOD
) {
    die("Unauthorized");
}

if (!isset($_POST['post_id'])) {
    die("Invalid request");
}

$postId = (int)$_POST['post_id'];

$stmt = mysqli_prepare(
    $db_connect,
    "DELETE FROM images WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $postId);
mysqli_stmt_execute($stmt);

header("Location: index.php");
exit;
