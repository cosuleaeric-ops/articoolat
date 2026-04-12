<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

$settings = load_settings();
$db = get_db();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && verify_csrf($_POST['csrf'] ?? '')) {
    $stmt = $db->prepare("DELETE FROM articles WHERE id = :id");
    $stmt->bindValue(':id', intval($_POST['delete_id']), SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: /admin/articles.php?deleted=1');
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id']) && verify_csrf($_POST['csrf'] ?? '')) {
    $stmt = $db->prepare("UPDATE articles SET title = :title, description = :desc, tag = :tag, url = :url WHERE id = :id");
    $stmt->bindValue(':title', trim($_POST['title']), SQLITE3_TEXT);
    $stmt->bindValue(':desc', trim($_POST['description']), SQLITE3_TEXT);
    $stmt->bindValue(':tag', trim($_POST['tag']), SQLITE3_TEXT);
    $stmt->bindValue(':url', trim($_POST['url']), SQLITE3_TEXT);
    $stmt->bindValue(':id', intval($_POST['edit_id']), SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: /admin/articles.php?saved=1');
    exit;
}

$sort = $_GET['sort'] ?? 'date';
$order = $sort === 'votes' ? 'votes DESC' : 'created_at DESC';
$result = $db->query("SELECT * FROM articles ORDER BY $order");
$articles = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $articles[] = $row;
}
$tags = get_tags();

$edit_id = intval($_GET['edit'] ?? 0);
$edit_article = null;
if ($edit_id) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $edit_article = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen flex">
    <?php include __DIR__ . '/bar.php'; ?>

    <main class="flex-1 p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Articole</h1>
            <div class="flex gap-2">
                <a href="?sort=date" class="px-3 py-1 text-xs rounded-full <?= $sort !== 'votes' ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">Dupa data</a>
                <a href="?sort=votes" class="px-3 py-1 text-xs rounded-full <?= $sort === 'votes' ? 'bg-accent text-white' : 'bg-surface text-muted' ?>">Dupa voturi</a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
        <p class="text-green-400 text-sm mb-4">Articol sters.</p>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
        <p class="text-green-400 text-sm mb-4">Articol salvat.</p>
        <?php endif; ?>

        <?php if ($edit_article): ?>
        <div class="bg-surface rounded-xl p-6 mb-6">
            <h2 class="font-semibold mb-4">Editeaza articolul</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="edit_id" value="<?= $edit_article['id'] ?>">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div>
                    <label class="block text-sm text-muted mb-1">Titlu</label>
                    <input type="text" name="title" value="<?= e($edit_article['title']) ?>" class="w-full bg-bg border border-bg rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm text-muted mb-1">URL</label>
                    <input type="url" name="url" value="<?= e($edit_article['url']) ?>" class="w-full bg-bg border border-bg rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm text-muted mb-1">Descriere</label>
                    <textarea name="description" rows="2" class="w-full bg-bg border border-bg rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent resize-none"><?= e($edit_article['description']) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm text-muted mb-1">Categorie</label>
                    <select name="tag" class="bg-bg border border-bg rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                        <?php foreach ($tags as $t): ?>
                        <option value="<?= e($t) ?>" <?= $edit_article['tag'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90">Salveaza</button>
                    <a href="/admin/articles.php" class="bg-surface text-muted px-4 py-2 rounded-lg text-sm hover:text-txt">Anuleaza</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-surface rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bg text-muted text-left">
                            <th class="px-4 py-3 font-medium">Titlu</th>
                            <th class="px-4 py-3 font-medium">Tag</th>
                            <th class="px-4 py-3 font-medium text-center">Voturi</th>
                            <th class="px-4 py-3 font-medium">Data</th>
                            <th class="px-4 py-3 font-medium">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                        <tr class="border-b border-bg hover:bg-bg/50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="<?= e($article['url']) ?>" target="_blank" class="hover:text-accent transition-colors"><?= e(truncate($article['title'], 60)) ?></a>
                                <div class="text-xs text-muted"><?= e($article['domain']) ?></div>
                            </td>
                            <td class="px-4 py-3"><span class="bg-bg px-2 py-0.5 rounded text-xs"><?= e($article['tag']) ?></span></td>
                            <td class="px-4 py-3 text-center font-semibold"><?= $article['votes'] ?></td>
                            <td class="px-4 py-3 text-muted text-xs"><?= date('d.m.Y H:i', strtotime($article['created_at'])) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="?edit=<?= $article['id'] ?>&sort=<?= $sort ?>" class="text-accent hover:underline text-xs">Editeaza</a>
                                    <form method="POST" onsubmit="return confirm('Sigur stergi?')">
                                        <input type="hidden" name="delete_id" value="<?= $article['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <button type="submit" class="text-red-400 hover:underline text-xs">Sterge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($articles)): ?>
                        <tr><td colspan="5" class="px-4 py-8 text-center text-muted">Niciun articol.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
