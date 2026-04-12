<?php
require_once __DIR__ . '/includes/functions.php';
$s = load_settings();
$s['color_bg'] = '#f3f4f6';
$s['color_surface'] = '#ffffff';
save_settings($s);
echo 'Colors updated.';
