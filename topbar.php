<?php
// Expected variables (set by including file):
// - $_SESSION
// - $user (array|null)
// - $postCount (int)
?>
<h1>ISKA'S WEB IMAGE GALLERY</h1>
<div style="margin-bottom:10px;">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php"><button>Logout</button></a>
        <a href="upload.php"><button>Upload Image</button></a>
    <?php else: ?>
        <a href="login.php"><button>Login</button></a>
        <a href="registration.php"><button>Register</button></a>
        <a href="upload.php"><button>Upload Image</button></a>
    <?php endif; ?>
</div>

<?php if (isset($user) && $user): ?>
    <div style="display:inline-block; vertical-align:top; border:3px solid gray; padding:5px;">
        <div style="display:flex;">
            <div>
                <img
                    src="img_pfp/<?= htmlspecialchars($user['profile_picture'] ?? 'default.png') ?>"
                    width="150"
                    height="150"
                    style="object-fit:cover; border:3px solid blue;"
                    alt="Profile Picture">
            </div>

            <div style="margin-left:8px;">
                <div>Username: <?= htmlspecialchars($user['username']) ?></div>
                <div>Joined: <?= date("F d, Y", strtotime($user['created_at'])) ?></div>
                <div>Posts uploaded: <?= (int)$postCount ?></div>
                <div>User ID: <?= (int)$user['id'] ?></div>
            </div>
        </div>
    </div><br><br>

    <?php endif; ?>
    <form method="GET" action="tags.php">
        <input type="text" name="q" placeholder="Search tags..." required>
        <button type="submit">Search</button>
    </form>