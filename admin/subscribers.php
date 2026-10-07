<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

$settings = load_settings();
$db = get_db();

$db->exec("CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$result = $db->query("SELECT email, created_at FROM subscribers ORDER BY created_at DESC");
$subscribers = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $subscribers[] = $row;
}
$total = count($subscribers);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers.csv"');
    echo "Email,Data\n";
    foreach ($subscribers as $sub) {
        echo $sub['email'] . ',' . $sub['created_at'] . "\n";
    }
    exit;
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
            <h1 class="text-2xl font-bold">Newsletter <span class="text-muted text-lg font-normal">(<?= $total ?>)</span></h1>
            <?php if ($total > 0): ?>
            <a href="?export=csv" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
                Export CSV
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($subscribers)): ?>
        <p class="text-muted">Niciun abonat încă.</p>
        <?php else: ?>
        <div class="bg-surface rounded-xl border border-muted/20 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-muted/20 text-muted text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-left px-4 py-3">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $i => $sub): ?>
                    <tr class="border-b border-muted/10 hover:bg-muted/5">
                        <td class="px-4 py-3 text-muted"><?= $total - $i ?></td>
                        <td class="px-4 py-3 font-medium"><?= e($sub['email']) ?></td>
                        <td class="px-4 py-3 text-muted"><?= date('d M Y, H:i', strtotime($sub['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
