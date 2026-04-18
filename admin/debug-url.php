<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth();

$url = trim($_GET['url'] ?? '');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <style>
        pre { background:#1c1917; color:#e7e5e4; padding:16px; border-radius:8px; font-size:11px; overflow:auto; max-height:600px; white-space:pre-wrap; word-break:break-all; }
        .section { margin-bottom:24px; }
        h2 { font-size:14px; font-weight:600; margin-bottom:8px; color:#a8a29e; text-transform:uppercase; letter-spacing:.05em; }
    </style>
</head>
<body class="bg-bg text-txt min-h-screen flex">
    <?php include __DIR__ . '/bar.php'; ?>
    <main class="flex-1 p-8 max-w-4xl">
        <h1 class="text-2xl font-bold mb-6">Debug URL</h1>

        <form method="get" class="flex gap-3 mb-8">
            <input type="text" name="url" value="<?= htmlspecialchars($url) ?>"
                   placeholder="https://substack.com/@gurwinder/p-182779879"
                   class="flex-1 bg-surface border border-border rounded-lg px-4 py-2 text-sm">
            <button type="submit" class="bg-accent text-white px-6 py-2 rounded-lg font-semibold">Testează</button>
        </form>

        <?php if ($url): ?>
        <?php
            // 1. Direct HTML fetch
            $html_direct = fetch_article_html($url) ?: '';
            $canonical = $html_direct ? extract_canonical_url($html_direct) : null;
            $text_direct = $html_direct ? extract_article_text($html_direct) : '';
            $wc_direct = count_words($text_direct);

            // 2. Jina pe URL original
            $jina_orig = fetch_article_text_via_reader($url);
            $wc_jina_orig = count_words($jina_orig);

            // 3. Jina pe canonical (dacă există)
            $jina_canonical = '';
            $wc_jina_canonical = 0;
            if ($canonical && $canonical !== $url) {
                $jina_canonical = fetch_article_text_via_reader($canonical);
                $wc_jina_canonical = count_words($jina_canonical);
            }

            // 4. Rezultat final compute_reading_time
            $final = compute_reading_time($url);
        ?>

        <div class="section">
            <h2>Rezultat final: <?= $final ?> min</h2>
            <?php if ($canonical && $canonical !== $url): ?>
                <p class="text-sm text-muted mb-2">Canonical rezolvat: <strong><?= htmlspecialchars($canonical) ?></strong></p>
            <?php else: ?>
                <p class="text-sm text-muted mb-2">Nu s-a găsit canonical diferit de URL original.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>1. Direct HTML fetch — <?= $wc_direct ?> cuvinte</h2>
            <pre><?= htmlspecialchars(mb_substr($text_direct, 0, 2000)) ?><?= strlen($text_direct) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>

        <div class="section">
            <h2>2. Jina pe URL original — <?= $wc_jina_orig ?> cuvinte</h2>
            <pre><?= htmlspecialchars(mb_substr($jina_orig, 0, 2000)) ?><?= strlen($jina_orig) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>

        <?php if ($canonical && $canonical !== $url): ?>
        <div class="section">
            <h2>3. Jina pe canonical — <?= $wc_jina_canonical ?> cuvinte</h2>
            <pre><?= htmlspecialchars(mb_substr($jina_canonical, 0, 2000)) ?><?= strlen($jina_canonical) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</body>
</html>
