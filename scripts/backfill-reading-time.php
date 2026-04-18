<?php
/**
 * Backfill reading_time pentru articolele existente.
 * Re-fetchează HTML-ul și recalculează folosind estimate_reading_time().
 *
 * Rulează: php scripts/backfill-reading-time.php
 * Flag --all recalculează pentru TOATE articolele (altfel doar cele cu valori lipsă
 * sau absurde >60min).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$all = in_array('--all', $argv ?? [], true);
$db = get_db();

$where = $all ? '1=1' : '(reading_time IS NULL OR reading_time < 1 OR reading_time > 60)';
$result = $db->query("SELECT id, url, reading_time FROM articles WHERE {$where} ORDER BY id");

$updated = 0;
$failed = 0;

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $id = $row['id'];
    $url = $row['url'];
    $old = $row['reading_time'];

    $html = fetch_article_html($url);
    if (!$html) {
        // Fallback: minim 3 min dacă nu putem ajunge la pagină
        $new = 3;
        $failed++;
        $tag = 'FETCH-FAIL';
    } else {
        $new = estimate_reading_time($html);
        $tag = 'OK';
    }

    $stmt = $db->prepare("UPDATE articles SET reading_time = :rt WHERE id = :id");
    $stmt->bindValue(':rt', $new, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    $updated++;

    printf("[%s] #%d  %s  ->  %s min  (%s)\n",
        $tag, $id, $old === null ? 'null' : $old . ' min', $new, $url);
}

echo "\nGata. Actualizate: $updated (fetch eșuat: $failed)\n";
