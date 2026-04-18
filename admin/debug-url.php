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
            // 0. Substack: profil → subdomain → API articol
            $substack = parse_substack_url($url);
            $substack_text = '';
            $substack_debug = '';
            if ($substack) {
                $username = $substack['username'] ?? '';
                $post_id  = $substack['post_id'];
                $subdomain = null;
                if ($username) {
                    $profile = substack_json_fetch("https://substack.com/api/v1/user/$username/public_profile");
                    $subdomain = $profile['primaryPublication']['subdomain']
                        ?? $profile['primary_publication']['subdomain']
                        ?? $profile['publication']['subdomain']
                        ?? null;
                }
                $substack_debug = "username=$username  post_id=$post_id  subdomain=" . ($subdomain ?? 'N/A');
                $substack_text = fetch_substack_post_text($username, $post_id);
            }
            $wc_substack = count_words($substack_text);

            // 1. Direct HTML fetch
            $html_direct = fetch_article_html($url) ?: '';
            $canonical = $html_direct ? extract_canonical_url($html_direct) : null;
            $text_direct = $html_direct ? extract_article_text($html_direct) : '';
            $wc_direct = count_words($text_direct);

            // 2. Jina pe URL original
            $jina_orig = fetch_article_text_via_reader($url);
            $wc_jina_orig = count_words($jina_orig);

            // 3. Rezultat final compute_reading_time
            $final = compute_reading_time($url);
        ?>

        <div class="section">
            <h2>Rezultat final: <?= $final ?> min</h2>
        </div>

        <?php if ($substack): ?>
        <div class="section">
            <h2>0. Substack API — <?= $wc_substack ?> cuvinte</h2>
            <p class="text-xs text-muted mb-2"><?= htmlspecialchars($substack_debug) ?></p>
            <pre><?= htmlspecialchars(mb_substr($substack_text, 0, 2000)) ?><?= strlen($substack_text) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>1. Direct HTML fetch — <?= $wc_direct ?> cuvinte</h2>
            <pre><?= htmlspecialchars(mb_substr($text_direct, 0, 2000)) ?><?= strlen($text_direct) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>

        <div class="section">
            <h2>2. Jina pe URL original — <?= $wc_jina_orig ?> cuvinte</h2>
            <pre><?= htmlspecialchars(mb_substr($jina_orig, 0, 2000)) ?><?= strlen($jina_orig) > 2000 ? "\n... [trunchiat]" : '' ?></pre>
        </div>

        <?php endif; ?>
    </main>
</body>
</html>
