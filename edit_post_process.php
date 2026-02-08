<?php
session_start();
require_once "db_connect.php";

define('ROLE_MOD', 2);

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] < ROLE_MOD) {
    die("Unauthorized");
}

$postId = (int)$_POST['post_id'];
$description = trim($_POST['description']);
$oldImage = $_POST['old_image'];

// Handle new image
$newImageName = $oldImage;
if (!empty($_FILES['new_image']['name'])) {
    $tmpName = $_FILES['new_image']['tmp_name'];
    $originalName = basename($_FILES['new_image']['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];

    if (!in_array($ext, $allowed)) die("Invalid image type");

    $newImageName = uniqid('img_', true) . '.' . $ext;
    $targetPath = "img_dir/" . $newImageName;

    if (!move_uploaded_file($tmpName, $targetPath)) die("Failed to upload image");

    if ($oldImage !== 'default.png' && file_exists("img_dir/$oldImage")) {
        unlink("img_dir/$oldImage");
    }
}

// Update image description and file
$stmt = mysqli_prepare($db_connect, "UPDATE images SET description = ?, image_file = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ssi", $description, $newImageName, $postId);
mysqli_stmt_execute($stmt);

// --- Handle tags ---
$tagsInput = trim($_POST['tags']); // e.g., "cat dog artist:john"
$newTags = array_filter(array_map('trim', explode(' ', $tagsInput)));

// Get old tags for decrementing usage_count
$oldTagQuery = mysqli_prepare($db_connect, "
    SELECT t.id, t.usage_count 
    FROM tags t 
    INNER JOIN image_tags it ON t.id = it.tag_id 
    WHERE it.image_id = ?");
mysqli_stmt_bind_param($oldTagQuery, "i", $postId);
mysqli_stmt_execute($oldTagQuery);
$oldTagResult = mysqli_stmt_get_result($oldTagQuery);

$oldTags = [];
while ($row = mysqli_fetch_assoc($oldTagResult)) {
    $oldTags[$row['id']] = $row['usage_count'];
}

// Delete old image_tags
$stmtDel = mysqli_prepare($db_connect, "DELETE FROM image_tags WHERE image_id = ?");
mysqli_stmt_bind_param($stmtDel, "i", $postId);
mysqli_stmt_execute($stmtDel);

// Decrement usage_count for old tags
foreach ($oldTags as $tagId => $count) {
    $stmtDec = mysqli_prepare($db_connect, "UPDATE tags SET usage_count = usage_count - 1 WHERE id = ? AND usage_count > 0");
    mysqli_stmt_bind_param($stmtDec, "i", $tagId);
    mysqli_stmt_execute($stmtDec);
}

// Insert new tags and relations
foreach ($newTags as $tagName) {
    // Check if tag exists
    $stmtTag = mysqli_prepare($db_connect, "SELECT id FROM tags WHERE name = ?");
    mysqli_stmt_bind_param($stmtTag, "s", $tagName);
    mysqli_stmt_execute($stmtTag);
    $resultTag = mysqli_stmt_get_result($stmtTag);
    $tag = mysqli_fetch_assoc($resultTag);

    if ($tag) {
        $tagId = $tag['id'];
        // Increment usage_count
        $stmtInc = mysqli_prepare($db_connect, "UPDATE tags SET usage_count = usage_count + 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmtInc, "i", $tagId);
        mysqli_stmt_execute($stmtInc);
    } else {
        // Insert new tag
        $stmtIns = mysqli_prepare($db_connect, "INSERT INTO tags (name, usage_count) VALUES (?, 1)");
        mysqli_stmt_bind_param($stmtIns, "s", $tagName);
        mysqli_stmt_execute($stmtIns);
        $tagId = mysqli_insert_id($db_connect);
    }

    // Associate tag with image
    $stmtAssoc = mysqli_prepare($db_connect, "INSERT INTO image_tags (image_id, tag_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmtAssoc, "ii", $postId, $tagId);
    mysqli_stmt_execute($stmtAssoc);
}

mysqli_query($db_connect, "DELETE FROM tags WHERE usage_count < 1");

// Done
header("Location: index.php");
exit;
