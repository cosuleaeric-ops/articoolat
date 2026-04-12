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

$db = get_db();
$ip = get_client_ip();

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

// Update article votes count
$db->exec("UPDATE articles SET votes = votes + 1 WHERE id = $article_id");

// Get new count
$stmt = $db->prepare("SELECT votes FROM articles WHERE id = :id");
$stmt->bindValue(':id', $article_id, SQLITE3_INTEGER);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

echo json_encode(['success' => true, 'voted' => true, 'votes' => $row['votes']]);
