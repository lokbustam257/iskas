<?php
session_start();
require_once "db_connect.php";
require_once "config.php";

// Determine if it’s a single tag or a search query
$searchTags = [];
$queryTitle = '';

if (isset($_GET['tag']) && trim($_GET['tag']) !== '') {
    $searchTags = [strtolower(trim($_GET['tag']))];
    $queryTitle = 'Tag: ' . htmlspecialchars($searchTags[0]);
} elseif (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $searchTags = array_filter(array_map('strtolower', explode(' ', trim($_GET['q']))));
    $queryTitle = 'Search: ' . htmlspecialchars(trim($_GET['q']));
} else {
    header("Location: index.php");
    exit;
}

// Fetch logged-in user (for topbar)
$user = null;
$postCount = 0;
$isMod = false;
if (isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($db_connect,
        "SELECT id, username, created_at, profile_picture FROM users WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    $stmt = mysqli_prepare($db_connect,
        "SELECT COUNT(*) AS post_count FROM images WHERE uploader_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $countRow = mysqli_fetch_assoc($result);
    $postCount = (int)$countRow['post_count'];

    $isMod = isset($_SESSION['role_id']) && $_SESSION['role_id'] >= ROLE_MOD;
}

// Pagination setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$imagesPerPage = IMAGES_PER_PAGE;
$offset = ($page - 1) * $imagesPerPage;

// Build placeholders for prepared statement
$tagPlaceholders = implode(',', array_fill(0, count($searchTags), '?'));
$types = str_repeat('s', count($searchTags)); // all strings

// Count total images that match all tags
$countSql = "
    SELECT COUNT(*) AS total
    FROM images
    WHERE id IN (
        SELECT image_id
        FROM image_tags
        INNER JOIN tags ON image_tags.tag_id = tags.id
        WHERE tags.name IN ($tagPlaceholders)
        GROUP BY image_id
        HAVING COUNT(DISTINCT tags.name) = ?
    )
";
$stmt = mysqli_prepare($db_connect, $countSql);
$typesWithCount = $types . 'i';
$params = array_merge($searchTags, [count($searchTags)]);
mysqli_stmt_bind_param($stmt, $typesWithCount, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalImages = (int)mysqli_fetch_assoc($result)['total'];
$totalPages = max(1, ceil($totalImages / $imagesPerPage));
mysqli_stmt_close($stmt);

// Fetch images for current page
$fetchSql = "
    SELECT images.*, users.username,
        GROUP_CONCAT(tags.name ORDER BY tags.name SEPARATOR ', ') AS tags
    FROM images
    INNER JOIN image_tags ON images.id = image_tags.image_id
    INNER JOIN tags ON image_tags.tag_id = tags.id
    LEFT JOIN users ON images.uploader_id = users.id
    WHERE images.id IN (
        SELECT image_id
        FROM image_tags
        INNER JOIN tags ON image_tags.tag_id = tags.id
        WHERE tags.name IN ($tagPlaceholders)
        GROUP BY image_id
        HAVING COUNT(DISTINCT tags.name) = ?
    )
    GROUP BY images.id
    ORDER BY images.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($db_connect, $fetchSql);
$typesWithCountAndLimit = $types . 'iii';
$params = array_merge($searchTags, [count($searchTags), $imagesPerPage, $offset]);
mysqli_stmt_bind_param($stmt, $typesWithCountAndLimit, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<?php include 'topbar.php'; ?><br>

<h1><?= $queryTitle ?></h1>
<a href="index.php"><button type="button">Back to index.php</button></a>
<?php if ($totalImages === 0): ?>
    <p><i>No images found matching your criteria.</i></p>
<?php endif; ?>

<style>
    .img-display {
        max-width: 300px;
        max-height: 300px;
        object-fit: cover;
    }
    .image-card {
        max-width: 300px;
        max-height: 400px;
        overflow-y: auto;
        border: 3px solid blue;
        display: inline-block;
        vertical-align: top;
        margin: 4px 2px;
        padding: 5px 8px;
    }
</style>

<div class="index-images">
    <?php while ($data = mysqli_fetch_assoc($result)): ?>
        <div class="image-card">
            <span class="img-display" style="display:flex; justify-content:center;">
                <a href="./img_dir/<?= htmlspecialchars($data['image_file']) ?>" target="_blank" style="text-decoration:none;">
                    <img class="img-display" src="./img_dir/<?= htmlspecialchars($data['image_file']) ?>" alt="Image <?= (int)$data['id'] ?>">
                </a>
            </span>
            <br>
            <span>
                <span>Post ID: <?= (int)$data['id'] ?></span>
                <?php if ($isMod): ?>
                    <a href="edit_post.php?id=<?= (int)$data['id'] ?>"><button>Edit</button></a>
                    <form action="delete_post.php" method="POST" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?= (int)$data['id'] ?>">
                        <button type="submit" onclick="return confirm('Delete this post?')">Delete</button>
                    </form>
                <?php endif; ?>
                <br>
                <span>Posted by: <?= $data['username'] ? htmlspecialchars($data['username']) : '<i style="color:gray;">Anonymous</i>' ?></span><br>
                <span>Description: <?= $data['description'] ? htmlspecialchars($data['description']) : '<i>No description</i>' ?></span><br>
                <span>Posted on: <?= date("F d, Y", strtotime($data['created_at'])) ?></span><br>
                <span>Tags: 
                    <?php
                    if ($data['tags']) {
                        foreach (explode(', ', $data['tags']) as $tag) {
                            echo '<a href="tags.php?tag=' . urlencode($tag) . '">' . htmlspecialchars($tag) . '</a> ';
                        }
                    } else {
                        echo '<i style="color:gray;">No tags</i>';
                    }
                    ?>
                </span>
            </span>
        </div>
    <?php endwhile; mysqli_stmt_close($stmt); ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="margin-top:20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?<?= isset($_GET['q']) ? 'q=' . urlencode($_GET['q']) : 'tag=' . urlencode($_GET['tag']) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>
