<?php
require_once __DIR__ . '/includes/functions.php';
$s = load_settings();
$s['site_subtitle'] = 'Internetul e plin de articole bune. Problema e că nu le găsești.';
$s['heading_size'] = '36';
$s['ga_measurement_id'] = $s['ga_measurement_id'] ?? '';
$s['kit_api_secret'] = $s['kit_api_secret'] ?? '';
unset($s['umami_site_id'], $s['umami_script_url'], $s['tags']);
save_settings($s);
echo 'Settings updated.';
