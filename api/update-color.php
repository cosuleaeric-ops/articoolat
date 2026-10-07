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
$color = trim($input['color'] ?? '');

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    echo json_encode(['success' => false, 'error' => 'Invalid hex color']);
    exit;
}

$settings = load_settings();
$settings['color_accent'] = $color;
save_settings($settings);

echo json_encode(['success' => true, 'color' => $color]);
