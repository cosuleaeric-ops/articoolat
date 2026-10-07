<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$url = trim($input['url'] ?? '');
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$image_url = trim($input['image_url'] ?? '');
$tag = trim($input['tag'] ?? 'General');
$submitted_by = trim($input['submitted_by'] ?? '') ?: 'Anonim';
$reading_time = intval($input['reading_time'] ?? 0);

if (!$url || !$title) {
    echo json_encode(['success' => false, 'error' => 'URL si titlu sunt obligatorii']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'URL invalid']);
    exit;
}

$domain = extract_domain($url);

$db = get_db();

// Check duplicate
$stmt = $db->prepare("SELECT id FROM articles WHERE url = :url");
$stmt->bindValue(':url', $url, SQLITE3_TEXT);
if ($stmt->execute()->fetchArray()) {
    echo json_encode(['success' => false, 'error' => 'Acest articol a fost deja adaugat']);
    exit;
}

$stmt = $db->prepare("INSERT INTO articles (url, title, description, image_url, domain, tag, submitted_by, reading_time)
                       VALUES (:url, :title, :desc, :img, :domain, :tag, :by, :rt)");
$stmt->bindValue(':url', $url, SQLITE3_TEXT);
$stmt->bindValue(':title', $title, SQLITE3_TEXT);
$stmt->bindValue(':desc', $description, SQLITE3_TEXT);
$stmt->bindValue(':img', $image_url, SQLITE3_TEXT);
$stmt->bindValue(':domain', $domain, SQLITE3_TEXT);
$stmt->bindValue(':tag', $tag, SQLITE3_TEXT);
$stmt->bindValue(':by', $submitted_by, SQLITE3_TEXT);
$stmt->bindValue(':rt', $reading_time ?: null, SQLITE3_INTEGER);
$stmt->execute();

// Auto-vote for submitter
$article_id = $db->lastInsertRowID();
$ip = get_client_ip();
$vstmt = $db->prepare("INSERT INTO votes (article_id, voter_ip) VALUES (:aid, :ip)");
$vstmt->bindValue(':aid', $article_id, SQLITE3_INTEGER);
$vstmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$vstmt->execute();

echo json_encode(['success' => true, 'id' => $article_id]);
