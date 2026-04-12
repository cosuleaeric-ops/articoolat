<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

$settings = load_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $settings['ga_measurement_id'] = trim($_POST['ga_measurement_id'] ?? '');
    $settings['kit_api_key'] = trim($_POST['kit_api_key'] ?? '');
    $settings['kit_api_secret'] = trim($_POST['kit_api_secret'] ?? '');

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

            <!-- Google Analytics -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Google Analytics</h2>
                <label class="block text-sm text-muted mb-1">Measurement ID</label>
                <input type="text" name="ga_measurement_id" value="<?= e($settings['ga_measurement_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX"
                       class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
            </div>

            <!-- Kit -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Kit (ConvertKit)</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-muted mb-1">API Key</label>
                        <input type="text" name="kit_api_key" value="<?= e($settings['kit_api_key'] ?? '') ?>" placeholder="API key"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-sm text-muted mb-1">API Secret</label>
                        <input type="text" name="kit_api_secret" value="<?= e($settings['kit_api_secret'] ?? '') ?>" placeholder="API secret"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt placeholder-muted focus:outline-none focus:border-accent">
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                Salveaza setarile
            </button>
        </form>
    </main>
</body>
</html>
