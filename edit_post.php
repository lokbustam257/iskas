<?php
session_start();
require_once "db_connect.php";

define('ROLE_MOD', 2);

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] < ROLE_MOD) {
    die("Unauthorized");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid post ID");
}

$postId = (int)$_GET['id'];

// Fetch post
$stmt = mysqli_prepare(
    $db_connect,
    "SELECT images.id, images.description, images.image_file, users.username 
     FROM images
     LEFT JOIN users ON images.uploader_id = users.id
     WHERE images.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $postId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);

if (!$post) die("Post not found");

// Fetch associated tags
$stmtTags = mysqli_prepare(
    $db_connect,
    "SELECT tags.name 
     FROM tags 
     INNER JOIN image_tags ON tags.id = image_tags.tag_id 
     WHERE image_tags.image_id = ?"
);
mysqli_stmt_bind_param($stmtTags, "i", $postId);
mysqli_stmt_execute($stmtTags);
$resultTags = mysqli_stmt_get_result($stmtTags);

$tagNames = [];
while ($tag = mysqli_fetch_assoc($resultTags)) {
    $tagNames[] = $tag['name'];
}
?>

<h1>Edit Post</h1>

<form action="edit_post_process.php" method="POST" enctype="multipart/form-data" id="edit-post-form">
    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
    <input type="hidden" name="old_image" value="<?= htmlspecialchars($post['image_file']) ?>">

    <span>
        Uploaded by: <?= $post['username'] ? htmlspecialchars($post['username']) : '<i style="color:gray;">Anonymous</i>' ?>
    </span><br><br>

    <label>Current Image</label><br>
    <img src="img_dir/<?= htmlspecialchars($post['image_file']) ?>" style="max-width:300px; border:3px solid gray;"><br><br>

    <label>Replace Image</label><br>
    <input type="file" name="new_image" id="new_image" accept="image/*" onchange="previewImage(this)"><br><br>
    <label for="new_preview" class="js-only">New image preview</label><br>
    <img id="new_preview" class="js-only" style="max-width:300px; border:3px dashed blue; display:none;"><br><br>

    <label>Description</label><br>
    <textarea name="description" rows="5" cols="40"><?= htmlspecialchars($post['description']) ?></textarea><br><br>

    <label>Tags</label><br>
    <div id="tag-container"></div>
    <input type="text" id="tag-input" placeholder="Add a tag"><br><br>

    <button type="submit">Save changes</button>
    <a href="index.php"><button type="button">Cancel (back to index.php)</button></a>
</form>

<script>
const tagContainer = document.getElementById('tag-container');
const tagInput = document.getElementById('tag-input');

// Initialize tags from PHP
let tags = <?= json_encode($tagNames) ?>;
renderTags();

// Render tags as chips
function renderTags() {
    tagContainer.innerHTML = '';
    tags.forEach(tag => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.textContent = tag + ' ';
        const removeBtn = document.createElement('span');
        removeBtn.className = 'remove-tag';
        removeBtn.textContent = '×';
        removeBtn.onclick = () => removeTag(tag);
        chip.appendChild(removeBtn);
        tagContainer.appendChild(chip);
    });

    // Update hidden input to submit tags as space-separated string
    let hiddenInput = document.querySelector('input[name="tags"]');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'tags';
        document.getElementById('edit-post-form').appendChild(hiddenInput);
    }
    hiddenInput.value = tags.join(' ');
}

// Add new tag
tagInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const newTag = tagInput.value.trim();
        if (newTag && !tags.includes(newTag)) {
            tags.push(newTag);
            renderTags();
        }
        tagInput.value = '';
    }
});

// Remove tag
function removeTag(tag) {
    tags = tags.filter(t => t !== tag);
    renderTags();
}

// Preview new image
function previewImage(input) {
    const preview = document.getElementById('new_preview');
    const label = document.querySelector('label[for="new_preview"]');

    if (input.files && input.files[0]) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.style.display = "block";
        label.style.display = "inline";
    } else {
        preview.src = "";
        preview.style.display = "none";
        label.style.display = "none";
    }
}

// --- Auto-suggest ---
tagInput.addEventListener('input', function() {
    const query = tagInput.value.trim();
    if (!query) return;

    fetch(`tags_suggest.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(suggestions => {
            // Show suggestions dropdown
            let dropdown = document.getElementById('suggestions');
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.id = 'suggestions';
                dropdown.style.border = '1px solid #ccc';
                dropdown.style.position = 'absolute';
                dropdown.style.background = 'white';
                dropdown.style.zIndex = 100;
                dropdown.style.maxWidth = tagInput.offsetWidth + 'px';
                tagInput.parentNode.appendChild(dropdown);
            }
            dropdown.innerHTML = '';
            suggestions.forEach(s => {
                const div = document.createElement('div');
                div.textContent = s;
                div.style.padding = '4px';
                div.style.cursor = 'pointer';
                div.onmousedown = () => {
                    if (!tags.includes(s)) tags.push(s);
                    renderTags();
                    tagInput.value = '';
                    dropdown.innerHTML = '';
                };
                dropdown.appendChild(div);
            });
        });
});

// Close suggestions if clicking outside
document.addEventListener('click', function(e) {
    if (!tagContainer.contains(e.target) && e.target !== tagInput) {
        const dropdown = document.getElementById('suggestions');
        if (dropdown) dropdown.innerHTML = '';
    }
});
</script>

<style>
.tag-chip {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 3px 8px;
    margin: 2px;
    border-radius: 12px;
    font-size: 14px;
}
.remove-tag {
    margin-left: 5px;
    cursor: pointer;
}
.js-only {
    display: none;
}
#tag-container {
    min-height: 30px;
    padding: 5px;
    border: 1px solid #ccc;
}
</style>
