<?php
session_start();
require_once "db_connect.php";

if (isset($_POST['upload'])) {
    $description = trim($_POST['description']);
    $description = ($description === '') ? null : $description;
    $uploader_id = $_SESSION['user_id'] ?? null;

    // Process tags from hidden input
    $raw_tags = trim($_POST['tags'] ?? '');
    $tags = [];
    if ($raw_tags !== '') {
        $tags = preg_split('/\s+/', $raw_tags);
    }

    // Allowed file extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $image_uploaded = false;

    foreach ($_FILES['uploadimage']['name'] as $index => $originalName) {
        if ($_FILES['uploadimage']['error'][$index] !== UPLOAD_ERR_OK) continue;

        $tmpName = $_FILES['uploadimage']['tmp_name'][$index];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) continue;

        $safeName = uniqid('img_', true) . '.' . $extension;
        $target = "img_dir/" . $safeName;

        if (!move_uploaded_file($tmpName, $target)) continue;

        // Insert image record
        $stmt = mysqli_prepare(
            $db_connect,
            "INSERT INTO images (image_file, description, uploader_id) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssi", $safeName, $description, $uploader_id);
        mysqli_stmt_execute($stmt);
        $image_id = mysqli_insert_id($db_connect);

        // Insert tags and link with usage_count
        foreach ($tags as $tag) {
            $tag = strtolower(trim($tag));
            $tag = str_replace(' ', '_', $tag);
            if ($tag === '') continue;

            // Insert or update tag usage_count
            $stmtTag = mysqli_prepare(
                $db_connect,
                "INSERT INTO tags (name, usage_count) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE usage_count = usage_count + 1, id = LAST_INSERT_ID(id)"
            );
            mysqli_stmt_bind_param($stmtTag, "s", $tag);
            mysqli_stmt_execute($stmtTag);
            $tag_id = mysqli_insert_id($db_connect);

            // Link image to tag
            $stmtLink = mysqli_prepare(
                $db_connect,
                "INSERT IGNORE INTO image_tags (image_id, tag_id) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmtLink, "ii", $image_id, $tag_id);
            mysqli_stmt_execute($stmtLink);
        }

        $image_uploaded = true;
    }

    // Success / failure message
    if ($image_uploaded) {
        echo "<p style='color:green;'>Upload successful!</p>";
    } else {
        echo "<p style='color:red;'>Upload failed! No file selected or unsupported file type (jpg, jpeg, png, gif only).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload</title>
    <style>
        .js-only {
            display: none;
        }

        #previews img {
            max-width: 150px;
            margin: 5px;
            border: 2px dashed blue;
        }

        #tag-container {
            min-height: 30px;
            padding: 5px;
            border: 1px solid #ccc;
            margin-bottom: 5px;
        }

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

        #suggestions {
            border: 1px solid #ccc;
            position: absolute;
            background: white;
            z-index: 100;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <form method="POST" enctype="multipart/form-data" id="upload-form">
        <label>Upload images (max 2MB each)</label><br>
        <input type="file" name="uploadimage[]" accept="image/*" multiple onchange="previewImages(this)"><br><br>

        <div id="previews" class="js-only"></div>

        <label>Description</label><br>
        <textarea name="description"></textarea><br><br>

        <label>Tags</label><br>
        <div id="tag-container"></div>
        <input type="text" id="tag-input" placeholder="Add a tag"><br><br>

        <button type="submit" name="upload">Upload Images</button>
    </form>

    <a href="index.php"><button type="button">Back to index</button></a>

    <script>
        const tagContainer = document.getElementById('tag-container');
        const tagInput = document.getElementById('tag-input');
        let tags = [];

        // Render tags
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

            // Hidden input for form submission
            let hiddenInput = document.querySelector('input[name="tags"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'tags';
                document.getElementById('upload-form').appendChild(hiddenInput);
            }
            hiddenInput.value = tags.join(' ');
        }

        // Add tag
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

        // Auto-suggest
        tagInput.addEventListener('input', function() {
            const query = tagInput.value.trim();
            if (!query) return;
            fetch(`tags_suggest.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(suggestions => {
                    let dropdown = document.getElementById('suggestions');
                    if (!dropdown) {
                        dropdown = document.createElement('div');
                        dropdown.id = 'suggestions';
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
            if (e.target !== tagInput && !tagContainer.contains(e.target)) {
                const dropdown = document.getElementById('suggestions');
                if (dropdown) dropdown.innerHTML = '';
            }
        });

        // Preview images
        function previewImages(input) {
            const previews = document.getElementById('previews');
            previews.innerHTML = '';
            if (input.files.length > 0) {
                for (let i = 0; i < input.files.length; i++) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(input.files[i]);
                    previews.appendChild(img);
                }
                previews.style.display = 'block';
            } else {
                previews.style.display = 'none';
            }
        }
    </script>

</body>

</html>