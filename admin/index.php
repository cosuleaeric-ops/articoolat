<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$settings = load_settings();

// Logout
if (isset($_GET['logout'])) {
    setcookie('art_auth', '', time() - 3600, '/');
    header('Location: /admin/');
    exit;
}

// Handle first-time setup or login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $pass = $_POST['password'];

    if (empty($settings['admin_password_hash'])) {
        // First time: set password
        $settings['admin_password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        save_settings($settings);
        $valid = true;
    } else {
        $valid = password_verify($pass, $settings['admin_password_hash']);
    }

    if ($valid) {
        $cookie = hash_hmac('sha256', 'art_admin_ok', $settings['auth_secret']);
        setcookie('art_auth', $cookie, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        header('Location: /admin/');
        exit;
    } else {
        $error = 'Parola incorecta.';
    }
}

// If not authenticated, show login
if (!is_authenticated()):
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen flex items-center justify-center">
    <div class="bg-surface rounded-2xl p-8 w-full max-w-sm">
        <h1 class="text-xl font-bold mb-1"><?= e($settings['site_title']) ?> Admin</h1>
        <p class="text-muted text-sm mb-6">
            <?= empty($settings['admin_password_hash']) ? 'Seteaza parola de admin:' : 'Introdu parola:' ?>
        </p>
        <?php if (isset($error)): ?>
        <p class="text-red-400 text-sm mb-4"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" required autofocus placeholder="Parola"
                   class="w-full bg-white border border-muted/20 rounded-lg px-4 py-3 text-txt placeholder-muted focus:outline-none focus:border-accent mb-4">
            <button type="submit" class="w-full bg-accent text-white py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                <?= empty($settings['admin_password_hash']) ? 'Seteaza parola' : 'Conecteaza-te' ?>
            </button>
        </form>
    </div>
</body>
</html>
<?php exit; endif; ?>

<?php
// Dashboard
$db = get_db();
$total_articles = $db->querySingle("SELECT COUNT(*) FROM articles");
$total_votes = $db->querySingle("SELECT COALESCE(SUM(votes), 0) FROM articles");
$today_articles = $db->querySingle("SELECT COUNT(*) FROM articles WHERE created_at >= datetime('now', '-1 day')");
$top_article = $db->querySingle("SELECT title FROM articles ORDER BY votes DESC LIMIT 1");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen flex">
    <?php include __DIR__ . '/bar.php'; ?>

    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-surface rounded-xl p-5 border-l-4 border-accent">
                <p class="text-xs text-muted uppercase tracking-wide">Total articole</p>
                <p class="text-3xl font-bold mt-1"><?= $total_articles ?></p>
            </div>
            <div class="bg-surface rounded-xl p-5 border-l-4 border-blue-500">
                <p class="text-xs text-muted uppercase tracking-wide">Total voturi</p>
                <p class="text-3xl font-bold mt-1"><?= $total_votes ?></p>
            </div>
            <div class="bg-surface rounded-xl p-5 border-l-4 border-green-500">
                <p class="text-xs text-muted uppercase tracking-wide">Articole azi</p>
                <p class="text-3xl font-bold mt-1"><?= $today_articles ?></p>
            </div>
            <div class="bg-surface rounded-xl p-5 border-l-4 border-purple-500">
                <p class="text-xs text-muted uppercase tracking-wide">Top articol</p>
                <p class="text-sm font-medium mt-1 truncate"><?= $top_article ? e($top_article) : '—' ?></p>
            </div>
        </div>
    </main>
</body>
</html>
