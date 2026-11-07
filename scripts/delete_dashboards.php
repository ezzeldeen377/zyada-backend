<?php
$files = [
    '/Users/ezzeldeen/node_projects/zyada-backend/resources/views/admin-views/dashboard-food.blade.php',
    '/Users/ezzeldeen/node_projects/zyada-backend/resources/views/admin-views/dashboard-ecommerce.blade.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // 1. Delete "Accepted by Delivery Man" (wrapped in comments or fully)
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="col-sm-6 col-lg-3">\s*<a class="order--card[^>]+>.*?Accepted by Delivery Man.*?<\/div>\s*<\/a>\s*<\/div>\s*\-\-\}\}/su',
        '',
        $content
    );

    // 2. Delete "Out for Delivery"
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="col-sm-6 col-lg-3">\s*<a class="order--card[^>]+>.*?Out for Delivery.*?<\/div>\s*<\/a>\s*<\/div>\s*\-\-\}\}/su',
        '',
        $content
    );

    // 3. Delete leftover unassigned orders commented in ecommerce
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="col-sm-6 col-lg-3">\s*<a class="order--card[^>]+>.*?unassigned_orders.*?<\/div>\s*<\/a>\s*<\/div>/su',
        '',
        $content
    );

    // 4. Delete chart label
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="chart--label">.*?delivery_man.*?<\/div>\s*\-\-\}\}/su',
        '',
        $content
    );

    // 5. Delete top-deliveryman-view card full wrapper containing includes
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="col-lg-4 col-md-6">\s*<!-- Card -->\s*\{\{\-\-\s*<div class="card h-100" id="top-deliveryman-view">.*?<\/div>\s*\-\-\}\}\s*<!-- End Card -->\s*<\/div>\s*\-\-\}\}/su',
        '',
        $content
    );

    // General fallback for top-deliveryman-view if nested differently
    $content = preg_replace(
        '/\{\{\-\-\s*<div class="col-lg-4 col-md-6">.*?id="top-deliveryman-view".*?<\/div>\s*\-\-\}\}/su',
        '',
        $content
    );

    file_put_contents($file, $content);
}

echo "Dashboard components completely Deleted successfully\n";
