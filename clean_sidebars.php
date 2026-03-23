<?php
$dir = '/Users/ezzeldeen/node_projects/zyada-backend/resources/views/layouts/admin/partials/';
$files = glob($dir . '_sidebar_*.blade.php');

foreach ($files as $file) {
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // List of keys we want to HIDE
    $keys = ['scheduled', 'processing', 'item_on_the_way', 'failed', 'offline_verification_list', 'offline'];

    foreach ($keys as $key) {
        // Regex matches <li class="nav-item containing the routes we want to hide
        if ($key === 'offline_verification_list' || $key === 'offline') {
            $regex = '/(<li class="nav-item\s*\{\{\s*Request::is\([^)]+offline[^)]+\)[^>]+>.*?<\/li>)/su';
        } else {
            $regex = '/(<li class="nav-item\s*\{\{\s*Request::is\([^)]+' . $key . '[^)]+\)[^>]+>.*?<\/li>)/su';
        }

        $content = preg_replace_callback(
            $regex,
            function($m) {
                // Return commented block
                return "{{-- " . $m[1] . " --}}";
            },
            $content
        );
    }

    file_put_contents($file, $content);
}

echo "Submodule sidebars successfully commented out\n";
