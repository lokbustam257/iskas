<?php

set_time_limit(0);

$directory = __DIR__ . '/img_dir'; // CHANGE THIS
$dryRun = false; // set to true to test without deleting

$sizes = [];
$hashes = [];

/**
 * STEP 1: group by file size
 */
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $sizes[$file->getSize()][] = $file->getPathname();
    }
}

/**
 * STEP 2: hash only potential duplicates
 */
foreach ($sizes as $size => $files) {
    if (count($files) < 2) {
        continue;
    }

    foreach ($files as $path) {
        $hash = hash_file('sha256', $path);
        $hashes[$hash][] = $path;
    }
}

/**
 * STEP 3: delete duplicates (keep first)
 */
foreach ($hashes as $hash => $files) {
    if (count($files) < 2) {
        continue;
    }

    echo "Duplicate group:\n";
    echo "Keeping: {$files[0]}\n";

    for ($i = 1; $i < count($files); $i++) {
        echo "Deleting: {$files[$i]}\n";
        if (!$dryRun) {
            unlink($files[$i]);
        }
    }

    echo "\n";
}

echo "Done.\n";
