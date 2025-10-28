<?php

$cacheDirectoryPath = __DIR__ . '/cache';

if (!is_dir($cacheDirectoryPath)) {
    // If it doesn't exist, attempt to create it
    // The third argument 'true' enables recursive creation of parent directories
    // The second argument '0777' sets the permissions for the directory
    if (!mkdir($cacheDirectoryPath, 0777, true)) {
        // Handle the case where directory creation fails
        die('Failed to create directory: ' . $cacheDirectoryPath);
    }
}