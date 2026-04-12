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
        <div class="max-w-[36rem] mx-auto px-4">
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
        <h2 class="font-bold leading-tight tracking-tight" style="font-size: <?= e($settings['heading_size'] ?? '36') ?>px"><?= e($settings['site_subtitle']) ?></h2>

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
        <?php
        $thumb = $article['image_url'] ?: 'https://api.microlink.io/?url=' . urlencode($article['url']) . '&screenshot=true&meta=false&embed=screenshot.url';
        ?>
        <article class="card-hover flex items-center gap-4 p-4 rounded-xl bg-surface shadow-[0_1px_3px_rgba(0,0,0,0.08)] mb-3">
            <!-- Vote button -->
            <div class="flex flex-col items-center w-10 flex-shrink-0">
                <button onclick="vote(<?= $article['id'] ?>, this)"
                        class="vote-btn text-xl <?= isset($voted_ids[$article['id']]) ? 'voted' : 'text-muted hover:text-accent' ?>"
                        <?= isset($voted_ids[$article['id']]) ? 'disabled' : '' ?>>
                    ♥
                </button>
                <span class="text-sm font-semibold mt-0.5 vote-count"><?= $article['votes'] ?></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <a href="<?= e($article['url']) ?>" target="_blank" rel="noopener"
                   class="text-txt font-medium hover:text-accent transition-colors leading-snug line-clamp-2">
                    <?= e($article['title']) ?>
                </a>
                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-muted">
                    <span class="bg-muted/10 px-2 py-0.5 rounded"><?= e($article['domain']) ?></span>
                    <?php if ($article['reading_time']): ?>
                    <span>·</span>
                    <span class="inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><?= $article['reading_time'] ?> min</span>
                    <?php endif; ?>
                    <span>·</span>
                    <span><?= e($article['submitted_by']) ?></span>
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="w-14 h-14 flex-shrink-0 rounded-lg overflow-hidden bg-muted/10">
                <img src="<?= e($thumb) ?>" alt="" loading="lazy"
                     class="w-14 h-14 object-cover">
            </div>
        </article>
        <?php endforeach; ?>
    </main>

    <?php if ($is_admin): ?>
    <!-- Admin Edit Button -->
    <button onclick="document.getElementById('editPanel').classList.toggle('translate-x-full')"
            class="fixed bottom-4 right-4 z-50 bg-txt text-bg px-4 py-2 rounded-full text-sm font-semibold shadow-lg hover:opacity-90 transition-opacity">
        Editează
    </button>

    <!-- Edit Panel -->
    <div id="editPanel" class="fixed top-0 right-0 z-50 h-full w-80 bg-surface shadow-2xl transform translate-x-full transition-transform duration-300 overflow-y-auto">
        <div class="p-5">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg">Editează</h3>
                <button onclick="document.getElementById('editPanel').classList.add('translate-x-full')" class="text-muted hover:text-txt text-xl">&times;</button>
            </div>
            <form id="editForm" class="space-y-4">
                <div>
                    <label class="block text-xs text-muted mb-1">Titlu site</label>
                    <input type="text" name="site_title" value="<?= e($settings['site_title']) ?>"
                           class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1">Heading principal</label>
                    <textarea name="site_subtitle" rows="2"
                              class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent resize-none"><?= e($settings['site_subtitle']) ?></textarea>
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1">Footer</label>
                    <input type="text" name="site_footer" value="<?= e($settings['site_footer']) ?>"
                           class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                </div>
                <hr class="border-muted/20">
                <h4 class="font-semibold text-sm">Culori</h4>
                <?php
                $color_fields = [
                    'color_bg' => 'Background',
                    'color_surface' => 'Carduri',
                    'color_accent' => 'Accent',
                    'color_text' => 'Text',
                    'color_text_muted' => 'Text secundar',
                ];
                foreach ($color_fields as $key => $label):
                ?>
                <div class="flex items-center gap-2">
                    <input type="color" name="<?= $key ?>" value="<?= e($settings[$key]) ?>" class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent">
                    <input type="text" value="<?= e($settings[$key]) ?>" readonly class="w-20 bg-bg border border-muted/20 rounded px-2 py-1 text-xs font-mono"
                           onclick="this.previousElementSibling.click()">
                    <span class="text-xs text-muted"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
                <hr class="border-muted/20">
                <h4 class="font-semibold text-sm">Font heading</h4>
                <div>
                    <label class="block text-xs text-muted mb-1">Dimensiune (px)</label>
                    <input type="number" name="heading_size" value="<?= e($settings['heading_size'] ?? '36') ?>" min="20" max="60"
                           class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                </div>
                <button type="submit" class="w-full bg-accent text-white py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
                    Salvează
                </button>
            </form>
        </div>
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
    document.getElementById('editForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = new FormData(e.target);
        const body = Object.fromEntries(form.entries());
        const res = await fetch('/api/update-appearance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error || 'Eroare');
    });
    </script>
</body>
</html>
