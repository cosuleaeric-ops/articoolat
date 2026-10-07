<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_authenticated()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$settings = load_settings();

$allowed = ['site_title', 'site_subtitle', 'site_footer', 'color_bg', 'color_surface', 'color_accent', 'color_text', 'color_text_muted', 'heading_size', 'site_desc', 'nl_title', 'nl_desc', 'desc_size_desktop', 'desc_size_mobile', 'nl_title_size_desktop', 'nl_title_size_mobile', 'nl_desc_size_desktop', 'nl_desc_size_mobile'];

foreach ($allowed as $key) {
    if (isset($input[$key])) {
        $val = trim($input[$key]);
        if (str_starts_with($key, 'color_') && !preg_match('/^#[0-9a-fA-F]{6}$/', $val)) continue;
        if (str_ends_with($key, '_size') || str_ends_with($key, '_size_desktop') || str_ends_with($key, '_size_mobile')) {
            if (!is_numeric($val) || $val < 8 || $val > 100) continue;
        }
        $settings[$key] = $val;
    }
}

save_settings($settings);
echo json_encode(['success' => true]);
