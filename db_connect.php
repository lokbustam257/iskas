<?php
$db_connect = mysqli_connect(
    "localhost",
    "root",
    "",
    "iska_webgallerydb"
);

if (!$db_connect) {
    die("Database connection failed: " . mysqli_connect_error());
}
