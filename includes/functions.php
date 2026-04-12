<?php

function load_settings(): array {
    $file = __DIR__ . '/../data/settings.json';
    $defaults = [
        'site_title' => 'Articoolat',
        'site_subtitle' => 'Cele mai bune articole de pe internet, alese de tine.',
        'site_footer' => 'Articoolat — Pentru cei care preferă să citească.',
        'admin_password_hash' => '',
        'auth_secret' => bin2hex(random_bytes(32)),
        'umami_site_id' => '',
        'umami_script_url' => '',
        'kit_api_key' => '',
        'color_bg' => '#ffffff',
        'color_surface' => '#f5f5f4',
        'color_accent' => '#f97316',
        'color_text' => '#1c1917',
        'color_text_muted' => '#78716c',
        'tags' => 'Tehnologie,Startup,Cultură,Știință,Afaceri,Design,Programare,România,Educație,Opinie'
    ];

    if (file_exists($file)) {
        $loaded = json_decode(file_get_contents($file), true) ?: [];
        return array_merge($defaults, $loaded);
    }

    file_put_contents($file, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $defaults;
}

function save_settings(array $settings): void {
    $file = __DIR__ . '/../data/settings.json';
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function time_ago(string $datetime): string {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $then = new DateTime($datetime, new DateTimeZone('UTC'));
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . (($diff->y == 1) ? ' an' : ' ani') . ' în urmă';
    if ($diff->m > 0) return $diff->m . (($diff->m == 1) ? ' lună' : ' luni') . ' în urmă';
    if ($diff->d > 0) return $diff->d . (($diff->d == 1) ? ' zi' : ' zile') . ' în urmă';
    if ($diff->h > 0) return 'acum ' . $diff->h . (($diff->h == 1) ? ' oră' : ' ore');
    if ($diff->i > 0) return 'acum ' . $diff->i . ' min';
    return 'chiar acum';
}

function hot_score(int $votes, string $created_at): float {
    $now = time();
    $created = strtotime($created_at);
    $hours = ($now - $created) / 3600;
    return ($votes - 1) / pow($hours + 2, 1.5);
}

function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function extract_domain(string $url): string {
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    return preg_replace('/^www\./', '', $host);
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function truncate(string $text, int $length = 150): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function get_tags(): array {
    $settings = load_settings();
    return array_map('trim', explode(',', $settings['tags']));
}
