<?php
session_start();
require_once "db_connect.php";

define('ROLE_MOD', 2);

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] < ROLE_MOD
) {
    die("Unauthorized");
}

if (!isset($_POST['post_id'])) {
    header("Location: index.php");
    exit;
}

$postId = (int)$_POST['post_id'];

// Get image filename
$stmt = mysqli_prepare(
    $db_connect,
    "SELECT image_file FROM images WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $postId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    die("Post not found");
}

// Delete image file
$filePath = "img_dir/" . $post['image_file'];
if (file_exists($filePath)) {
    unlink($filePath);
}

// Delete DB record
$stmt = mysqli_prepare(
    $db_connect,
    "DELETE FROM images WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $postId);
mysqli_stmt_execute($stmt);

header("Location: index.php");
exit;
