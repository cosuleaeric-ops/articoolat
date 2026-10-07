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
$article_id = intval($input['article_id'] ?? 0);

if (!$article_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid article ID']);
    exit;
}

$fingerprint = trim($input['fingerprint'] ?? '');
$screen_resolution = trim($input['screen_resolution'] ?? '');
$timezone = trim($input['timezone'] ?? '');

$db = get_db();
$ip = get_client_ip();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

// Rate limiting: max 30 votes per fingerprint per hour
if ($fingerprint) {
    $rl = $db->prepare("SELECT COUNT(*) FROM vote_meta WHERE fingerprint = :fp AND created_at >= datetime('now', '-1 hour')");
    $rl->bindValue(':fp', $fingerprint, SQLITE3_TEXT);
    $count = $rl->execute()->fetchArray()[0];
    if ($count >= 30) {
        echo json_encode(['success' => false, 'error' => 'Prea multe voturi. Incearca mai tarziu.']);
        exit;
    }
}

// Check if already voted
$stmt = $db->prepare("SELECT id FROM votes WHERE article_id = :aid AND voter_ip = :ip");
$stmt->bindValue(':aid', $article_id, SQLITE3_INTEGER);
$stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$existing = $stmt->execute()->fetchArray();

if ($existing) {
    // Remove vote (unlike)
    $stmt = $db->prepare("DELETE FROM votes WHERE article_id = :aid AND voter_ip = :ip");
    $stmt->bindValue(':aid', $article_id, SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->execute();

    $db->exec("UPDATE articles SET votes = votes - 1 WHERE id = $article_id");

    $stmt = $db->prepare("SELECT votes FROM articles WHERE id = :id");
    $stmt->bindValue(':id', $article_id, SQLITE3_INTEGER);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    echo json_encode(['success' => true, 'voted' => false, 'votes' => $row['votes']]);
    exit;
}

// Insert vote
$stmt = $db->prepare("INSERT INTO votes (article_id, voter_ip) VALUES (:aid, :ip)");
$stmt->bindValue(':aid', $article_id, SQLITE3_INTEGER);
$stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$stmt->execute();

// Store vote metadata
$vote_id = $db->lastInsertRowID();
if ($fingerprint) {
    $meta = $db->prepare("INSERT INTO vote_meta (vote_id, fingerprint, user_agent, accept_language, screen_resolution, timezone) VALUES (:vid, :fp, :ua, :al, :sr, :tz)");
    $meta->bindValue(':vid', $vote_id, SQLITE3_INTEGER);
    $meta->bindValue(':fp', $fingerprint, SQLITE3_TEXT);
    $meta->bindValue(':ua', $user_agent, SQLITE3_TEXT);
    $meta->bindValue(':al', $accept_language, SQLITE3_TEXT);
    $meta->bindValue(':sr', $screen_resolution, SQLITE3_TEXT);
    $meta->bindValue(':tz', $timezone, SQLITE3_TEXT);
    $meta->execute();
}

// Update article votes count
$db->exec("UPDATE articles SET votes = votes + 1 WHERE id = $article_id");

// Get new count
$stmt = $db->prepare("SELECT votes FROM articles WHERE id = :id");
$stmt->bindValue(':id', $article_id, SQLITE3_INTEGER);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

echo json_encode(['success' => true, 'voted' => true, 'votes' => $row['votes']]);
