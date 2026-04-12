<?php
require_once __DIR__ . '/includes/functions.php';
$s = load_settings();
$s['color_bg'] = '#ffffff';
$s['color_surface'] = '#f5f5f4';
$s['color_text'] = '#1c1917';
$s['color_text_muted'] = '#78716c';
save_settings($s);
echo 'Colors updated to light mode.';
