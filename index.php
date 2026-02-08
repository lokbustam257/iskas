<?php
session_start();
require_once "db_connect.php";
require_once "config.php";

// Initialize user info
$user = null;
$postCount = 0;
$isMod = false;

if (isset($_SESSION['user_id'])) {
    // Fetch user info
    $stmt = mysqli_prepare(
        $db_connect,
        "SELECT id, username, created_at, profile_picture FROM users WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Count uploaded images
    $stmt = mysqli_prepare(
        $db_connect,
        "SELECT COUNT(*) FROM images WHERE uploader_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $postCount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Check if user is moderator or admin
    $isMod = isset($_SESSION['role_id']) && $_SESSION['role_id'] >= ROLE_MOD;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISKA'S WEB IMAGE GALLERY</title>
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
</head>
<body>

<!-- Topbar -->
<?php require "topbar.php"; ?>

<div class="index-images">
    <h2>Latest images</h2>
    <div>
        <?php
        // Pagination setup
        $imagesPerPage = IMAGES_PER_PAGE;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $imagesPerPage;

        // Count total images
        $countResult = mysqli_query($db_connect, "SELECT COUNT(*) AS total FROM images");
        $totalImages = (int)mysqli_fetch_assoc($countResult)['total'];
        $totalPages = max(1, ceil($totalImages / $imagesPerPage));

        // Fetch images with uploader and tags
        $stmt = mysqli_prepare(
            $db_connect,
            "SELECT images.*, users.username,
                GROUP_CONCAT(tags.name ORDER BY tags.name SEPARATOR ', ') AS tags
             FROM images
             LEFT JOIN users ON images.uploader_id = users.id
             LEFT JOIN image_tags ON images.id = image_tags.image_id
             LEFT JOIN tags ON image_tags.tag_id = tags.id
             GROUP BY images.id
             ORDER BY images.created_at DESC
             LIMIT ? OFFSET ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $imagesPerPage, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($data = mysqli_fetch_assoc($result)):
        ?>
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
                    <span>Description: <?= $data['description'] ? htmlspecialchars($data['description']) : '<i style="color:gray;">No description</i>' ?></span><br>
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

        <!-- Pagination -->
        <div style="margin-top:20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
</div>

</body>
</html>

<!-- simple fetch from db: -->
<!-- <?php 
    $connection_to_db = mysqli_connect("localhost", "root", "", "pasar");
    $query = "SELECT * FROM pasar";
    $result = mysqli_query($connection_to_db, $query);
?>

<html>
    <table>
        <?php while($datawhile = mysqli_fetch_assoc($result)): ?>
            <thead>
                <tr>
                    <th>id_barang</th>
                    <th>nama_barang</th>
                    <th>harga</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td> <?php echo $datawhile ['id_barang'] ?></td>
                    <td> <?php echo $datawhile ['nama_barang'] ?></td>
                    <td> <?php echo $datawhile ['harga'] ?></td>
                </tr>
            </tbody>
            <?php endwhile ?>
    </table>
</html> -->
<!-- 
<?php 
    $connection_to_db = mysqli_connect("localhost", "root", "", "database");
    $query = "SELECT * FROM tableex";
    $result = mysqli_query($connection_to_db, $query);
?>

<html>
    <table>
        <thead>
            <tr>
                <th>column1</th>
                <th>column2</th>
                <th>column3</th>
            </tr>
        </thead>
        
        <tbody>
            <?php while($whiledata = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $whiledata['column1'] ?></td>
                <td><?php echo $whiledata['column2'] ?></td>
                <td><?php echo $whiledata['column3'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</html> -->