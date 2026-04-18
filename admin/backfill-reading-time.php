<?php
/**
 * One-shot: recalculează reading_time pentru toate articolele.
 * Protejat prin auth admin. După ce rulezi și ești mulțumit, șterge fișierul.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth();

set_time_limit(600); // Jina Reader poate fi lent pe articole lungi
ini_set('memory_limit', '256M');

$run = isset($_GET['run']) && $_GET['run'] === '1';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <style>
        pre { background:#1c1917; color:#e7e5e4; padding:16px; border-radius:8px; font-size:12px; overflow:auto; max-height:500px; }
        .ok { color:#4ade80; }
        .fail { color:#f87171; }
    </style>
</head>
<body class="bg-bg text-txt min-h-screen flex">
    <?php include __DIR__ . '/bar.php'; ?>
    <main class="flex-1 p-8 max-w-3xl">
        <h1 class="text-2xl font-bold mb-4">Recalculează durata de citire</h1>
        <p class="text-muted text-sm mb-6">
            Re-descarcă HTML-ul fiecărui articol și recalculează câte minute durează citirea
            (între 1 și 120 min, fallback 3 min dacă pagina nu poate fi accesată).
            Rulează o singură dată, apoi șterge acest fișier din server.
        </p>

        <?php if (!$run): ?>
            <a href="?run=1" class="inline-block bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90">
                Rulează acum
            </a>
        <?php else:
            $db = get_db();
            $result = $db->query("SELECT id, url, reading_time FROM articles ORDER BY id");

            $lines = [];
            $updated = 0;
            $failed = 0;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $id  = $row['id'];
                $url = $row['url'];
                $old = $row['reading_time'];

                // Debug: fetch text and show word counts
                $is_js = is_js_heavy_domain($url);
                $reader = fetch_article_text_via_reader($url);
                $html_raw = !$is_js ? (fetch_article_html($url) ?: '') : '';
                $wc_reader = $reader ? count_words($reader) : 0;
                $wc_direct = $html_raw ? count_words(extract_article_text($html_raw)) : 0;
                $declared = extract_declared_reading_time($reader ?: $html_raw);

                $new = compute_reading_time($url);
                $tag = $new === 3 ? '<span class="fail">FALLBACK</span>' : '<span class="ok">OK</span>';
                if ($new === 3) $failed++;

                $stmt = $db->prepare("UPDATE articles SET reading_time = :rt WHERE id = :id");
                $stmt->bindValue(':rt', $new, SQLITE3_INTEGER);
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();
                $updated++;

                $lines[] = sprintf('[%s] #%d  %s->%d min  [jina:%dw direct:%dw declared:%s]  %s',
                    $tag, $id, $old === null ? 'null ' : $old.'min→', $new,
                    $wc_reader, $wc_direct, $declared ?? 'none', e($url));
            }
        ?>
            <div class="bg-surface rounded-xl p-6 mb-4">
                <p class="text-lg font-semibold mb-2">
                    Gata. <?= $updated ?> articole actualizate
                    <?php if ($failed): ?><span class="text-muted">(<?= $failed ?> fetch eșuate → setate la 3 min)</span><?php endif; ?>
                </p>
            </div>
            <pre><?= implode("\n", $lines) ?: 'Nu există articole.' ?></pre>
            <p class="text-sm text-muted mt-6">
                ⚠️ Șterge <code>admin/backfill-reading-time.php</code> din server după ce ești mulțumit de rezultat.
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
