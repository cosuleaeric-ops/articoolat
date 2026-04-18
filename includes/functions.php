<?php

function load_settings(): array {
    $file = __DIR__ . '/../data/settings.json';
    $defaults = [
        'site_title' => 'Articoolat',
        'site_subtitle' => 'Internetul e plin de articole bune. Problema e că nu le găsești.',
        'heading_size' => '36',
        'site_footer' => 'Articoolat — Pentru cei care preferă să citească.',
        'admin_password_hash' => '',
        'auth_secret' => bin2hex(random_bytes(32)),
        'ga_script' => '',
        'kit_api_key' => '',
        'kit_api_secret' => '',
        'color_bg' => '#f3f4f6',
        'color_surface' => '#ffffff',
        'color_accent' => '#f97316',
        'color_text' => '#1c1917',
        'color_text_muted' => '#78716c',
        'tags' => 'Tehnologie,Startup,Cultură,Știință,Afaceri,Design,Programare,România,Educație,Opinie',
        'new_days' => 7
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

/**
 * Descarcă HTML-ul unui URL folosind un User-Agent de Chrome real
 * (ca să nu fim blocați de Medium, Cloudflare etc.).
 * Returnează string-ul HTML sau false la eșec.
 */
function fetch_article_html(string $url) {
    $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    $headers = [
        "User-Agent: $ua",
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,ro;q=0.8',
        'Accept-Encoding: identity', // fără gzip ca să nu trebuiască să decodăm
        'Connection: close',
        'Upgrade-Insecure-Requests: 1',
    ];

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 12,
            'header' => implode("\r\n", $headers) . "\r\n",
            'follow_location' => true,
            'max_redirects' => 5,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    return @file_get_contents($url, false, $ctx);
}

/**
 * Estimate reading time in minutes from raw HTML.
 * Strips scripts/styles/nav/header/footer, prefers <article>/<main> content,
 * counts words and clamps to a sane range. Always returns >= 1.
 */
function estimate_reading_time(string $html): int {
    if (!$html) return 3;

    // Remove non-content blocks (script/style/noscript + nav/header/footer/aside + SVG)
    $clean = preg_replace('#<(script|style|noscript|template|svg)\b[^>]*>.*?</\1>#is', ' ', $html);
    $clean = preg_replace('#<(nav|header|footer|aside|form)\b[^>]*>.*?</\1>#is', ' ', $clean);
    // Drop HTML comments
    $clean = preg_replace('/<!--.*?-->/s', ' ', $clean);

    // Prefer the main article content if present
    $content = '';
    if (preg_match('#<article\b[^>]*>(.*?)</article>#is', $clean, $m)) {
        $content = $m[1];
    } elseif (preg_match('#<main\b[^>]*>(.*?)</main>#is', $clean, $m)) {
        $content = $m[1];
    } elseif (preg_match('#<div[^>]+(?:id|class)=["\'][^"\']*(post-content|entry-content|article-body|post-body|story-body)[^"\']*["\'][^>]*>(.*?)</div>#is', $clean, $m)) {
        $content = $m[2];
    } else {
        $content = $clean;
    }

    $text = strip_tags($content);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', trim($text));

    if (!$text) return 3;

    // Count words (Unicode-aware)
    $word_count = preg_match_all('/[\p{L}\p{N}]+/u', $text);
    if ($word_count < 50) return 3; // too little usable text — default

    $minutes = (int) round($word_count / 238); // 238 WPM avg
    return max(1, min(120, $minutes));
}
