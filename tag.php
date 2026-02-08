<?php
session_start();
require_once "db_connect.php";
require_once "config.php";

if (!isset($_GET['tag']) || trim($_GET['tag']) === '') {
    header("Location: index.php");
    exit;
}

$tag = strtolower(trim($_GET['tag']));

// Logged-in user info
$user = null;
$postCount = 0;
$isMod = false;
if (isset($_SESSION['user_id'])) {
    // Fetch user profile
    $stmt = mysqli_prepare(
        $db_connect,
        "SELECT id, username, created_at, profile_picture
         FROM users
         WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Count uploads
    $stmt = mysqli_prepare(
        $db_connect,
        "SELECT COUNT(*) AS post_count
         FROM images
         WHERE uploader_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $countRow = mysqli_fetch_assoc($result);
    $postCount = (int)$countRow['post_count'];

    // Check if user is mod/admin
    $isMod = isset($_SESSION['role_id']) && $_SESSION['role_id'] >= ROLE_MOD;
}

// Pagination setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$imagesPerPage = IMAGES_PER_PAGE;
$offset = ($page - 1) * $imagesPerPage;

// Count total images for this tag
$stmt = mysqli_prepare(
    $db_connect,
    "SELECT COUNT(DISTINCT images.id) AS total
     FROM images
     INNER JOIN image_tags ON images.id = image_tags.image_id
     INNER JOIN tags ON image_tags.tag_id = tags.id
     WHERE tags.name = ?"
);
mysqli_stmt_bind_param($stmt, "s", $tag);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalImages = (int)mysqli_fetch_assoc($result)['total'];
$totalPages = max(1, ceil($totalImages / $imagesPerPage));
mysqli_stmt_close($stmt);

// Fetch images for this page
$stmt = mysqli_prepare(
    $db_connect,
    "SELECT images.*, users.username,
        GROUP_CONCAT(tags.name ORDER BY tags.name SEPARATOR ', ') AS tags
     FROM images
     INNER JOIN image_tags ON images.id = image_tags.image_id
     INNER JOIN tags ON image_tags.tag_id = tags.id
     LEFT JOIN users ON images.uploader_id = users.id
     WHERE tags.name = ?
     GROUP BY images.id
     ORDER BY images.created_at DESC
     LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param($stmt, "sii", $tag, $imagesPerPage, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<?php include 'topbar.php'; ?><br>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tag: <?= htmlspecialchars($tag) ?></title>
    <style>
        .img-display img {
            max-width: 300px;
            max-height: 300px;
            border: 3px solid gray;
        }
        .image-card {
            max-width: 300px;
            border: 3px solid blue;
            display: inline-block;
            margin: 5px;
            padding: 5px;
            vertical-align: top;
        }
    </style>
</head>
<body>
<a href="index.php"><button type="button">Back to index</button></a><br>
<h1>Images tagged with: <?= htmlspecialchars($tag) ?></h1>

<?php if ($totalImages === 0): ?>
    <p><i>No images found for this tag.</i></p>
<?php endif; ?>

<?php while ($data = mysqli_fetch_assoc($result)): ?>
    <div class="image-card">
        <div class="img-display" style="text-align:center;">
            <a target="_blank" href="./img_dir/<?= htmlspecialchars($data['image_file']) ?>">
                <img src="./img_dir/<?= htmlspecialchars($data['image_file']) ?>" alt="">
            </a>
        </div>

        <div>
            <div>Post ID: <?= (int)$data['id'] ?></div>

            <?php if ($isMod): ?>
                <a href="edit_post.php?id=<?= (int)$data['id'] ?>"><button>Edit</button></a>
                <form action="delete_post.php" method="POST" style="display:inline;">
                    <input type="hidden" name="post_id" value="<?= (int)$data['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this post?')">Delete</button>
                </form>
            <?php endif; ?>

            <div>
                Posted by:
                <?= $data['username'] ? htmlspecialchars($data['username']) : '<i style="color:gray;">Anonymous</i>' ?>
            </div>
            <div>
                Description:
                <?= $data['description'] ? htmlspecialchars($data['description']) : '<i style="color:gray;">No description</i>' ?>
            </div>
            <div>
                Posted on: <?= date("F d, Y", strtotime($data['created_at'])) ?>
            </div>
            <div>
                <strong>Tags:</strong>
                <?php
                if ($data['tags']) {
                    foreach (explode(', ', $data['tags']) as $t) {
                        echo '<a href="tag.php?tag=' . urlencode($t) . '">' . htmlspecialchars($t) . '</a> ';
                    }
                } else {
                    echo '<i>No tags</i>';
                }
                ?>
            </div>
        </div>
    </div>
<?php endwhile; mysqli_stmt_close($stmt); ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="margin-top:20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?tag=<?= urlencode($tag) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

</body>
</html>
