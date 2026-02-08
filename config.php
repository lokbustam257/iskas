<?php

$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "iska_webgallerydb";

// User roles
define('ROLE_USER', 1);
define('ROLE_MOD', 2);
define('ROLE_ADMIN', 3);

// Global site settings
define('IMAGES_PER_PAGE', 15);   // <-- pagination setting
define('DEFAULT_PROFILE_PICTURE', 'default.png');
define('UPLOAD_DIR', './img_dir/');