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
            <div class="relative mb-4">
                <input type="password" id="passwordInput" name="password" required autofocus placeholder="Parola"
                       class="w-full bg-white border border-muted/20 rounded-lg px-4 py-3 pr-12 text-txt placeholder-muted focus:outline-none focus:border-accent">
                <button type="button" onmousedown="document.getElementById('passwordInput').type='text'" onmouseup="document.getElementById('passwordInput').type='password'" onmouseleave="document.getElementById('passwordInput').type='password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-txt transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
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
