<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/admin/auth.php';
$is_admin = is_authenticated();

$db = get_db();
$settings = load_settings();

$tab = $_GET['tab'] ?? 'new';
$top_period = $_GET['period'] ?? 'all';

// Build query based on tab
switch ($tab) {
    case 'new':
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 50";
        break;
    case 'top':
        $sql = "SELECT * FROM articles ORDER BY votes DESC LIMIT 50";
        break;
    default: // hot
        $tab = 'hot';
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 200";
        break;
}

$result = $db->query($sql);
$articles = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($tab === 'hot') {
        $row['_score'] = hot_score($row['votes'], $row['created_at']);
    }
    $articles[] = $row;
}

if ($tab === 'hot') {
    usort($articles, fn($a, $b) => $b['_score'] <=> $a['_score']);
    $articles = array_slice($articles, 0, 50);
}

// Check which articles user already voted on
$ip = get_client_ip();
$voted_ids = [];
$stmt = $db->prepare("SELECT article_id FROM votes WHERE voter_ip = :ip");
$stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$vr = $stmt->execute();
while ($v = $vr->fetchArray(SQLITE3_ASSOC)) {
    $voted_ids[$v['article_id']] = true;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen">

    <?php if ($is_admin): ?>
    <!-- Admin Top Bar -->
    <div class="bg-txt text-bg text-xs py-1.5">
        <div class="max-w-[36rem] mx-auto px-4 flex items-center justify-between">
            <span>Admin</span>
            <a href="/admin/" class="hover:underline">Panou admin →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Bar (floating) -->
    <div class="sticky top-0 z-50 pt-3 px-4">
        <nav class="max-w-[36rem] mx-auto bg-surface/95 backdrop-blur-sm shadow-[0_2px_12px_rgba(0,0,0,0.1)] rounded-full px-5 py-4 flex items-center justify-between">
            <a href="/" class="font-bristol text-xl uppercase tracking-tight hover:text-accent transition-colors">#ARTICOOLAT</a>
            <a href="/submit.php" class="bg-accent text-white px-4 py-1.5 rounded-full text-sm font-semibold hover:opacity-90 transition-opacity">
                + Articol nou
            </a>
        </nav>
    </div>

    <!-- Header -->
    <header class="max-w-[36rem] mx-auto px-4 pt-8 pb-4">
        <h2 class="text-[36px] font-bold leading-tight tracking-tight">Internetul e plin de articole bune. Problema e că nu le găsești.</h2>

        <!-- Tabs -->
        <nav class="flex gap-2 mt-6 border-b border-muted/20">
            <a href="/?tab=new"
               class="px-5 py-3 text-base font-semibold border-b-2 transition-colors <?= $tab === 'new' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                <svg class="inline w-4 h-4 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg> Noi
            </a>
            <a href="/?tab=top"
               class="px-5 py-3 text-base font-semibold border-b-2 transition-colors <?= $tab === 'top' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                <svg class="inline w-4 h-4 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> Top
            </a>
        </nav>

    </header>

    <!-- Articles -->
    <main class="max-w-[36rem] mx-auto px-4 pb-12">
        <?php if (empty($articles)): ?>
        <div class="text-center py-16">
            <p class="text-muted text-lg">Niciun articol inca.</p>
            <a href="/submit.php" class="text-accent hover:underline mt-2 inline-block">Fii primul care adauga unul!</a>
        </div>
        <?php endif; ?>

        <?php foreach ($articles as $i => $article): ?>
        <article class="card-hover flex items-start gap-4 p-4 rounded-xl bg-surface shadow-[0_1px_3px_rgba(0,0,0,0.08)] mb-3">
            <!-- Vote button -->
            <div class="flex flex-col items-center pt-1 min-w-[40px]">
                <button onclick="vote(<?= $article['id'] ?>, this)"
                        class="vote-btn text-xl <?= isset($voted_ids[$article['id']]) ? 'voted' : 'text-muted hover:text-accent' ?>"
                        <?= isset($voted_ids[$article['id']]) ? 'disabled' : '' ?>>
                    ♥
                </button>
                <span class="text-sm font-semibold mt-1 vote-count"><?= $article['votes'] ?></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <a href="<?= e($article['url']) ?>" target="_blank" rel="noopener"
                   class="text-txt font-medium hover:text-accent transition-colors leading-snug">
                    <?= e($article['title']) ?>
                </a>
                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-muted">
                    <span class="bg-surface px-2 py-0.5 rounded"><?= e($article['domain']) ?></span>
                    <?php if ($article['reading_time']): ?>
                    <span><?= $article['reading_time'] ?> min citire</span>
                    <?php endif; ?>
                    <span>·</span>
                    <span><?= e($article['submitted_by']) ?></span>
                </div>
            </div>

            <!-- Thumbnail -->
            <?php
            $thumb = $article['image_url'] ?: 'https://api.microlink.io/?url=' . urlencode($article['url']) . '&screenshot=true&meta=false&embed=screenshot.url';
            ?>
            <div class="hidden sm:block flex-shrink-0">
                <img src="<?= e($thumb) ?>" alt="" loading="lazy"
                     class="w-20 h-20 object-cover rounded-lg bg-surface">
            </div>
        </article>
        <?php endforeach; ?>
    </main>

    <?php if ($is_admin): ?>
    <!-- Admin Color Picker -->
    <div class="fixed bottom-4 right-4 z-50 bg-surface rounded-xl shadow-lg p-3 flex items-center gap-2">
        <label class="text-xs text-muted">Accent:</label>
        <input type="text" id="colorHex" value="<?= e($settings['color_accent']) ?>" maxlength="7"
               class="w-20 text-xs font-mono bg-bg border border-muted/20 rounded px-2 py-1 text-txt focus:outline-none focus:border-accent">
        <button onclick="saveColor()" class="text-xs bg-accent text-white px-3 py-1 rounded hover:opacity-90">OK</button>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="max-w-[36rem] mx-auto px-4 py-8 border-t border-muted/20">
        <p class="text-center text-muted text-sm"><?= e($settings['site_footer']) ?></p>
    </footer>

    <script>
    async function vote(articleId, btn) {
        if (btn.classList.contains('voted')) return;

        try {
            const res = await fetch('/api/vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId })
            });
            const data = await res.json();

            if (data.success) {
                btn.classList.add('voted');
                btn.disabled = true;
                btn.nextElementSibling.textContent = data.votes;
            }
        } catch (err) {
            console.error('Vote failed:', err);
        }
    }
    async function saveColor() {
        const hex = document.getElementById('colorHex').value.trim();
        if (!/^#[0-9a-fA-F]{6}$/.test(hex)) { alert('Format: #ff5500'); return; }
        const res = await fetch('/api/update-color.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ color: hex })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error);
    }
    </script>
</body>
</html>
