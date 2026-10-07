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

$allowed = ['site_title', 'site_subtitle', 'site_footer', 'color_bg', 'color_surface', 'color_accent', 'color_text', 'color_text_muted', 'heading_size'];

foreach ($allowed as $key) {
    if (isset($input[$key])) {
        $val = trim($input[$key]);
        if (str_starts_with($key, 'color_') && !preg_match('/^#[0-9a-fA-F]{6}$/', $val)) continue;
        if ($key === 'heading_size' && (!is_numeric($val) || $val < 20 || $val > 60)) continue;
        $settings[$key] = $val;
    }
}

save_settings($settings);
echo json_encode(['success' => true]);
