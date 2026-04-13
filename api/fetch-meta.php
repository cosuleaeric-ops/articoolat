<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'URL invalid']);
    exit;
}

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header' => "User-Agent: Mozilla/5.0 (compatible; Articoolat/1.0)\r\n",
        'follow_location' => true,
        'max_redirects' => 5
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$html = @file_get_contents($url, false, $ctx);

if (!$html) {
    echo json_encode(['success' => false, 'error' => 'Nu am putut accesa URL-ul']);
    exit;
}

// Extract OG tags
$title = '';
$description = '';
$image = '';

// og:title
if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
    $title = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
}
// Fallback to <title>
if (!$title && preg_match('/<title[^>]*>([^<]+)/i', $html, $m)) {
    $title = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
}

// og:description
if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
    $description = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
}
// Fallback to meta description
if (!$description && preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
    $description = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
}

// og:image
if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
    $image = $m[1];
    // Resolve relative URLs
    if ($image && !preg_match('#^https?://#i', $image)) {
        $parsed = parse_url($url);
        $image = $parsed['scheme'] . '://' . $parsed['host'] . '/' . ltrim($image, '/');
    }
}

// Estimate reading time: strip tags, count words, divide by 238 WPM
$text = strip_tags($html);
$text = preg_replace('/\s+/', ' ', $text);
$word_count = str_word_count($text);
$reading_time = max(1, round($word_count / 238));

echo json_encode([
    'success' => true,
    'title' => $title,
    'description' => $description,
    'image' => $image,
    'reading_time' => $reading_time
]);
