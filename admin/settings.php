<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

$settings = load_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    // Password change
    if (!empty($_POST['new_password'])) {
        $settings['admin_password_hash'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }

    $settings['umami_site_id'] = trim($_POST['umami_site_id'] ?? '');
    $settings['umami_script_url'] = trim($_POST['umami_script_url'] ?? '');
    $settings['kit_api_key'] = trim($_POST['kit_api_key'] ?? '');
    $settings['tags'] = trim($_POST['tags'] ?? $settings['tags']);

    save_settings($settings);
    $saved = true;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen flex">
    <?php include __DIR__ . '/bar.php'; ?>

    <main class="flex-1 p-8 max-w-2xl">
        <h1 class="text-2xl font-bold mb-6">Setari</h1>

        <?php if (isset($saved)): ?>
        <p class="text-green-400 text-sm mb-4">Setari salvate.</p>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <!-- Password -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Parola admin</h2>
                <input type="password" name="new_password" placeholder="Parola noua (lasa gol pt a nu schimba)"
                       class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
            </div>

            <!-- Analytics -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Umami Analytics</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-muted mb-1">Script URL</label>
                        <input type="url" name="umami_script_url" value="<?= e($settings['umami_script_url']) ?>" placeholder="https://analytics.eu.umami.is/script.js"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-sm text-muted mb-1">Site ID</label>
                        <input type="text" name="umami_site_id" value="<?= e($settings['umami_site_id']) ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
                    </div>
                </div>
            </div>

            <!-- Kit -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Kit (ConvertKit)</h2>
                <label class="block text-sm text-muted mb-1">API Key</label>
                <input type="text" name="kit_api_key" value="<?= e($settings['kit_api_key']) ?>" placeholder="API key"
                       class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
            </div>

            <!-- Tags -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Categorii / Tags</h2>
                <label class="block text-sm text-muted mb-1">Separate prin virgula</label>
                <input type="text" name="tags" value="<?= e($settings['tags']) ?>"
                       class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
            </div>

            <button type="submit" class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                Salveaza setarile
            </button>
        </form>
    </main>
</body>
</html>
