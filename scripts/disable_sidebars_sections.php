<?php
$dir = '/Users/ezzeldeen/node_projects/zyada-backend/resources/views/layouts/admin/partials/';
$files = glob($dir . '_sidebar*.blade.php');

foreach ($files as $file) {
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // 1. Disable Deliveryman Management section
    $content = preg_replace(
        '/@if\s*\(\s*\\\\App\\\\CentralLogics\\\\Helpers::module_permission_check\(\'deliveryman\'\)\s*\)/u',
        '@if (false && \\App\\CentralLogics\\Helpers::module_permission_check(\'deliveryman\'))',
        $content
    );

    // 2. Disable Promotion Management section
    $content = preg_replace(
        '/@if\s*\(\s*\\\\App\\\\CentralLogics\\\\Helpers::module_permission_check\(\'campaign\'\)/u',
        '@if (false && \\App\\CentralLogics\\Helpers::module_permission_check(\'campaign\')',
        $content
    );

    file_put_contents($file, $content);
}

echo "Submenu groups disabled via logic flags successfully\n";
