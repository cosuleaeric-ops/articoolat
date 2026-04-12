<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/admin/auth.php';
$is_admin = is_authenticated();

$db = get_db();
$settings = load_settings();

$tab = $_GET['tab'] ?? 'hot';
$top_period = $_GET['period'] ?? 'all';

// Build query based on tab
switch ($tab) {
    case 'new':
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 50";
        break;
    case 'top':
        $where = '';
        if ($top_period === 'today') {
            $where = "WHERE created_at >= datetime('now', '-1 day')";
        } elseif ($top_period === 'week') {
            $where = "WHERE created_at >= datetime('now', '-7 days')";
        } elseif ($top_period === 'month') {
            $where = "WHERE created_at >= datetime('now', '-30 days')";
        }
        $sql = "SELECT * FROM articles $where ORDER BY votes DESC LIMIT 50";
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
    <div class="bg-surface border-b border-muted/20 text-sm">
        <div class="max-w-3xl mx-auto px-4 py-2 flex items-center justify-between">
            <span class="text-muted">Admin</span>
            <a href="/admin/" class="text-accent hover:underline">Panou admin →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="max-w-3xl mx-auto px-4 pt-8 pb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    <a href="/" class="hover:text-accent transition-colors"><?= e($settings['site_title']) ?></a>
                </h1>
                <p class="text-muted text-sm mt-1"><?= e($settings['site_subtitle']) ?></p>
            </div>
            <a href="/submit.php" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
                + Adauga articol
            </a>
        </div>

        <!-- Tabs -->
        <nav class="flex gap-1 mt-6 border-b border-muted/20">
            <a href="/?tab=hot"
               class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'hot' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                🔥 Hot
            </a>
            <a href="/?tab=new"
               class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'new' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                ✨ Noi
            </a>
            <a href="/?tab=top"
               class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'top' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                📊 Top
            </a>
        </nav>

        <?php if ($tab === 'top'): ?>
        <div class="flex gap-2 mt-3">
            <?php foreach (['today' => 'Azi', 'week' => 'Saptamana', 'month' => 'Luna', 'all' => 'Toate'] as $key => $label): ?>
            <a href="/?tab=top&period=<?= $key ?>"
               class="px-3 py-1 text-xs rounded-full transition-colors <?= $top_period === $key ? 'bg-accent text-white' : 'bg-surface text-muted hover:text-txt' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>

    <!-- Articles -->
    <main class="max-w-3xl mx-auto px-4 pb-12">
        <?php if (empty($articles)): ?>
        <div class="text-center py-16">
            <p class="text-muted text-lg">Niciun articol inca.</p>
            <a href="/submit.php" class="text-accent hover:underline mt-2 inline-block">Fii primul care adauga unul!</a>
        </div>
        <?php endif; ?>

        <?php foreach ($articles as $i => $article): ?>
        <article class="card-hover flex items-start gap-4 py-4 px-3 rounded-xl <?= $i > 0 ? 'border-t border-muted/20' : '' ?>">
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
                    <span class="bg-surface px-2 py-0.5 rounded"><?= e($article['tag']) ?></span>
                    <?php if ($article['reading_time']): ?>
                    <span><?= $article['reading_time'] ?> min citire</span>
                    <?php endif; ?>
                    <span>·</span>
                    <span><?= e($article['submitted_by']) ?></span>
                    <span>·</span>
                    <span><?= time_ago($article['created_at']) ?></span>
                </div>
                <?php if ($article['description']): ?>
                <p class="text-muted text-sm mt-2 leading-relaxed"><?= e(truncate($article['description'], 200)) ?></p>
                <?php endif; ?>
            </div>

            <!-- Thumbnail -->
            <?php if ($article['image_url']): ?>
            <div class="hidden sm:block flex-shrink-0">
                <img src="<?= e($article['image_url']) ?>" alt="" loading="lazy"
                     class="w-20 h-20 object-cover rounded-lg bg-surface">
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer class="max-w-3xl mx-auto px-4 py-8 border-t border-muted/20">
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
    </script>
</body>
</html>
