<?php
require_once __DIR__ . '/includes/functions.php';
$s = load_settings();
$s['color_bg'] = '#f3f4f6';
$s['color_surface'] = '#ffffff';
$s['site_subtitle'] = 'Internetul e plin de articole bune. Problema e că nu le găsești.';
$s['heading_size'] = '36';
save_settings($s);
echo 'Fixed.';
