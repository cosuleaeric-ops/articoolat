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

        <!-- Abuse Detection -->
        <h2 class="text-xl font-bold mb-4">Detectie abuse voturi</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <?php
            // Top fingerprints by vote count
            $top_fp = $db->query("SELECT fingerprint, COUNT(*) as vote_count, COUNT(DISTINCT v.voter_ip) as ip_count, MIN(vm.created_at) as first_vote, MAX(vm.created_at) as last_vote FROM vote_meta vm JOIN votes v ON vm.vote_id = v.id GROUP BY fingerprint ORDER BY vote_count DESC LIMIT 10");
            ?>
            <div class="bg-surface rounded-xl p-5">
                <h3 class="font-semibold text-sm text-muted uppercase tracking-wide mb-3">Top fingerprints (dupa nr. voturi)</h3>
                <div class="space-y-2">
                    <?php
                    $has_rows = false;
                    while ($fp = $top_fp->fetchArray(SQLITE3_ASSOC)):
                        $has_rows = true;
                    ?>
                    <div class="flex items-center justify-between text-sm py-1.5 border-b border-muted/10">
                        <div>
                            <code class="text-xs bg-muted/10 px-1.5 py-0.5 rounded"><?= e(substr($fp['fingerprint'], 0, 12)) ?>...</code>
                            <span class="text-muted text-xs ml-1"><?= $fp['ip_count'] ?> IP-uri</span>
                        </div>
                        <span class="font-semibold"><?= $fp['vote_count'] ?> voturi</span>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$has_rows): ?>
                    <p class="text-muted text-sm">Nicio data inca.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Suspicious: fingerprints with multiple IPs (possible VPN abuse)
            $suspect = $db->query("SELECT fingerprint, COUNT(DISTINCT v.voter_ip) as ip_count, COUNT(*) as vote_count, GROUP_CONCAT(DISTINCT v.voter_ip) as ips FROM vote_meta vm JOIN votes v ON vm.vote_id = v.id GROUP BY fingerprint HAVING ip_count > 1 ORDER BY ip_count DESC LIMIT 10");
            ?>
            <div class="bg-surface rounded-xl p-5">
                <h3 class="font-semibold text-sm text-muted uppercase tracking-wide mb-3">Suspect: fingerprint pe mai multe IP-uri</h3>
                <div class="space-y-2">
                    <?php
                    $has_suspect = false;
                    while ($s = $suspect->fetchArray(SQLITE3_ASSOC)):
                        $has_suspect = true;
                    ?>
                    <div class="text-sm py-1.5 border-b border-muted/10">
                        <div class="flex items-center justify-between">
                            <code class="text-xs bg-muted/10 px-1.5 py-0.5 rounded"><?= e(substr($s['fingerprint'], 0, 12)) ?>...</code>
                            <span class="text-orange-500 font-semibold"><?= $s['ip_count'] ?> IP-uri</span>
                        </div>
                        <p class="text-xs text-muted mt-1"><?= e($s['ips']) ?></p>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$has_suspect): ?>
                    <p class="text-muted text-sm">Niciun comportament suspect.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
