<?php

require_once __DIR__ . '/../vendor/autoload.php';

use fivefilters\Readability\Readability;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Configuration;

function extract_article_text_readability(string $html, string $url = ''): string {
    if (!$html) return '';
    try {
        $config = new Configuration();
        if ($url) $config->setOriginalURL($url);
        $readability = new Readability($config);
        $readability->parse($html);
        $content = $readability->getContent() ?: '';
        return $content ? strip_tags($content) : '';
    } catch (ParseException $e) {
        return '';
    }
}

function load_settings(): array {
    $file = __DIR__ . '/../data/settings.json';
    $defaults = [
        'site_title' => 'Articoolat',
        'site_subtitle' => 'Internetul e plin de articole bune. Problema e că nu le găsești.',
        'heading_size' => '36',
        'site_footer' => 'Articoolat — Cele mai bune articole de pe Internet',
        'site_tab_description' => 'cele mai bune articole de pe internet',
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
        'new_days' => 7,
        'site_desc' => 'Noi le-am adunat pentru tine. Adaugă-ți și tu articolele preferate, și lasă un like celor mai bune.',
        'nl_title' => 'Vrei să primești articolele direct pe email?',
        'nl_desc' => 'Abonează-te la newsletter ca să primești săptămânal cele mai bune 3 articole regăsite pe Articoolat.',
        'desc_size_desktop' => '16',
        'desc_size_mobile' => '15',
        'nl_title_size_desktop' => '18',
        'nl_title_size_mobile' => '16',
        'nl_desc_size_desktop' => '14',
        'nl_desc_size_mobile' => '13'
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
/**
 * Încearcă să extragă URL-ul canonical dintr-un HTML.
 * Util pentru URL-uri Substack reader (substack.com/home/ sau substack.com/@author/)
 * care servesc preview limitat — canonicalul pointează la subdomain-ul complet.
 */
function extract_canonical_url(string $html): ?string {
    if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
        return trim($m[1]) ?: null;
    }
    if (preg_match('/<meta[^>]+property=["\']og:url["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        return trim($m[1]) ?: null;
    }
    return null;
}

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
 * Extract readable text from HTML: strips script/style/nav/header/footer,
 * prefers <article>/<main>/known content divs, returns plain text.
 */
function extract_article_text(string $html): string {
    if (!$html) return '';

    $clean = preg_replace('#<(script|style|noscript|template|svg)\b[^>]*>.*?</\1>#is', ' ', $html);
    $clean = preg_replace('#<(nav|header|footer|aside|form)\b[^>]*>.*?</\1>#is', ' ', $clean);
    $clean = preg_replace('/<!--.*?-->/s', ' ', $clean);

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
    return preg_replace('/\s+/u', ' ', trim($text));
}

/** Unicode-aware word count. */
function count_words(string $text): int {
    return $text ? (int) preg_match_all('/[\p{L}\p{N}]+/u', $text) : 0;
}

/**
 * Fallback: folosește r.jina.ai (Jina AI Reader) care randează paginile cu JS
 * și returnează textul curat. Gratuit, fără auth. Folosit când fetch direct
 * e blocat (Cloudflare challenge, Medium, X etc.).
 */
function fetch_article_text_via_reader(string $url): string {
    $reader_url = 'https://r.jina.ai/' . $url;

    // Cerem lui Jina să elimine comentariile și elementele non-articol din DOM
    $remove = implode(', ', [
        '#comments', '.comments', '.comments-area', '.comments-section',
        '.comment-list', '.comment-respond', '.wp-block-comments',
        '.post-comments', '#disqus_thread', '.disqus-comments',
        '.related-posts', '.related-articles', '.you-may-also-like',
        '.newsletter-signup', '.subscribe-form', '.sidebar',
        'footer', '.site-footer', '.post-footer',
    ]);

    $headers = implode("\r\n", [
        'User-Agent: Mozilla/5.0 Articoolat',
        'Accept: text/plain',
        'X-Remove-Selector: ' . $remove,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => $headers . "\r\n",
            'follow_location' => true,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $text = @file_get_contents($reader_url, false, $ctx);
    if (!$text) return '';

    // Taie la primul marker textual de comentarii / related — catch-all pentru
    // site-urile care nu folosesc clase standard
    $cutoff_patterns = [
        '/\n#+\s*(Comments?|Responses?|Discussion|Leave\s+a\s+(comment|reply)|Add\s+a\s+comment)\b/i',
        '/\n#+\s*(Related\s+(Posts?|Articles?|Stories?|Reading)|You\s+May\s+Also\s+(Like|Enjoy)|More\s+(from|on|like\s+this)|Keep\s+reading|Further\s+reading)\b/i',
        '/\n\*\*Comment\s+Rules/i',
        '/\n\d+\s+Comments?\s*\n/i',
        '/\nView\s+all\s+comments\b/i',
    ];
    foreach ($cutoff_patterns as $p) {
        if (preg_match($p, $text, $m, PREG_OFFSET_CAPTURE)) {
            $text = substr($text, 0, $m[0][1]);
        }
    }

    return preg_replace('/\s+/u', ' ', trim($text));
}

/**
 * Domenii unde HTML-ul direct e de obicei incomplet (SPA / JS heavy /
 * Cloudflare challenge). Pentru ele mergem direct la Jina Reader.
 */
function is_js_heavy_domain(string $url): bool {
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    $host = preg_replace('/^www\./', '', $host);
    $needles = ['medium.com', 'substack.com', 'x.com', 'twitter.com', 'threads.net', 'linkedin.com'];
    foreach ($needles as $n) {
        if ($host === $n || str_ends_with($host, '.' . $n)) return true;
    }
    return false;
}

/**
 * Extrage prima mențiune „N min read" dintr-un text (HTML sau markdown).
 * Multe platforme (Medium, Substack) calculează deja durata și o afișează
 * lângă autor — e cea mai precisă sursă.
 */
function extract_declared_reading_time(string $text): ?int {
    if (!$text) return null;
    preg_match_all('/(\d+)\s*min(?:ute)?s?\s*read/i', $text, $matches);
    if (empty($matches[1])) return null;
    $times = array_values(array_filter(array_map('intval', $matches[1]), fn($m) => $m >= 1 && $m <= 120));
    return $times ? $times[0] : null; // prima mențiune = articolul curent, nu cele recomandate
}

/**
 * Calculează durata de citire în minute pentru un URL.
 * Strategie:
 *   1. Fetch text via Jina Reader (sau direct pentru site-uri simple).
 *   2. Dacă avem ≥200 cuvinte — calculăm din word count (cel mai precis).
 *   3. Dacă textul e scurt (paywall/blocat) — fallback la timpul declarat de site.
 * Fallback final: 3 min.
 */
/**
 * Extrage username și post ID dintr-un URL Substack.
 * Returnează ['username' => '...', 'post_id' => '...'] sau null.
 */
function parse_substack_url(string $url): ?array {
    // substack.com/@username/p-ID
    if (preg_match('~substack\.com/@([^/?#]+)/p-(\d+)~i', $url, $m)) {
        return ['username' => $m[1], 'post_id' => $m[2]];
    }
    // substack.com/home/post/p-ID (reader URL, no username)
    if (preg_match('#substack\.com/home/post/p-(\d+)#i', $url, $m)) {
        return ['username' => null, 'post_id' => $m[1]];
    }
    return null;
}

function substack_json_fetch(string $url): ?array {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1)\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    return $json ? json_decode($json, true) : null;
}

/**
 * Obține textul complet al unui articol Substack gratuit via API:
 *   1. Lookup profil public → subdomain newsletter
 *   2. {subdomain}.substack.com/api/v1/posts/{id} → body_html
 */
function fetch_substack_post_text(string $username, string $post_id): string {
    // Pas 1: găsim subdomain-ul newsletterului din profilul public
    $subdomain = null;
    if ($username) {
        $profile = substack_json_fetch("https://substack.com/api/v1/user/$username/public_profile");
        $subdomain = $profile['primaryPublication']['subdomain']
            ?? $profile['primary_publication']['subdomain']
            ?? $profile['publication']['subdomain']
            ?? null;
    }

    // Pas 2: dacă am subdomain-ul, cerem articolul direct din API-ul publicației
    if ($subdomain) {
        $data = substack_json_fetch("https://$subdomain.substack.com/api/v1/posts/$post_id");
        $html = $data['body_html'] ?? '';
        if ($html) return extract_article_text($html);
    }

    // Fallback: încearcă API-ul general substack.com (funcționează uneori)
    $data = substack_json_fetch("https://substack.com/api/v1/post/$post_id");
    $html = $data['body_html'] ?? '';
    return $html ? extract_article_text($html) : '';
}

function compute_reading_time(string $url): int {
    $words = 0;

    // Pentru Substack: lookup profil → subdomain → API articol → body_html complet.
    $substack = parse_substack_url($url);
    if ($substack) {
        $text = fetch_substack_post_text($substack['username'] ?? '', $substack['post_id']);
        $words = count_words($text);
    }

    if ($words < 200) {
        $html = '';
        $reader = '';

        $html = '';
        $reader = '';

        if (is_js_heavy_domain($url)) {
            $reader = fetch_article_text_via_reader($url);
        } else {
            $html = fetch_article_html($url) ?: '';
        }

        $declared = extract_declared_reading_time($html ?: $reader);
        if ($declared !== null) return $declared;

        // Readability (Mozilla algoritm) — principala sursă pentru site-uri normale
        $words_readability = $html ? count_words(extract_article_text_readability($html, $url)) : 0;

        // Jina Reader — fallback pentru JS-heavy sau când Readability returnează prea puțin
        if ($words_readability < 200) {
            $reader = $reader ?: fetch_article_text_via_reader($url);
            $words_reader = $reader ? count_words($reader) : 0;
            $words = max($words, $words_readability, $words_reader);
        } else {
            $words = max($words, $words_readability);
        }

        if ($words < 200) {
            return 3;
        }
    }

    return max(1, min(120, (int) round($words / 238)));
}

/**
 * Compatibilitate: primește HTML și întoarce minute (folosit în cod vechi).
 */
function estimate_reading_time(string $html): int {
    $text = extract_article_text($html);
    $words = count_words($text);
    if ($words < 50) return 3;
    return max(1, min(120, (int) round($words / 238)));
}
