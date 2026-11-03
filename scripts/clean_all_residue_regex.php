<?php
$dir = '/Users/ezzeldeen/node_projects/zyada-backend/resources/views/';
$files = glob($dir . '**/*.blade.php');

// Include current directory level as well
$files = array_merge($files, glob($dir . '/*.blade.php'));
$files = array_merge($files, glob($dir . 'layouts/admin/partials/*.blade.php'));

foreach ($files as $file) {
    if (!file_exists($file) || is_dir($file)) continue;

    $content = file_get_contents($file);
    $changed = false;

    // Fix opening nested comment tags across spaces/newlines
    if (preg_match('/\{\{\-\-\s*\{\{\-\-/u', $content)) {
        $content = preg_replace('/\{\{\-\-\s*\{\{\-\-/u', '{{--', $content);
        $changed = true;
    }

    // Fix closing nested comment tags across spaces/newlines
    if (preg_match('/\-\-\}\}\s*\-\-\}\}/u', $content)) {
        $content = preg_replace('/\-\-\}\}\s*\-\-\}\}/u', '--}}', $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
    }
}

echo "All residual nested Blade tags cleaned successfully\n";
