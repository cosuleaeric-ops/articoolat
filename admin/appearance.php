<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

$settings = load_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $settings['site_title'] = trim($_POST['site_title'] ?? $settings['site_title']);
    $settings['site_tab_description'] = trim($_POST['site_tab_description'] ?? $settings['site_tab_description']);
    $settings['site_subtitle'] = trim($_POST['site_subtitle'] ?? $settings['site_subtitle']);
    $settings['site_footer'] = trim($_POST['site_footer'] ?? $settings['site_footer']);
    $settings['color_bg'] = trim($_POST['color_bg'] ?? $settings['color_bg']);
    $settings['color_surface'] = trim($_POST['color_surface'] ?? $settings['color_surface']);
    $settings['color_accent'] = trim($_POST['color_accent'] ?? $settings['color_accent']);
    $settings['color_text'] = trim($_POST['color_text'] ?? $settings['color_text']);
    $settings['color_text_muted'] = trim($_POST['color_text_muted'] ?? $settings['color_text_muted']);

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
        <h1 class="text-2xl font-bold mb-6">Aspect</h1>

        <?php if (isset($saved)): ?>
        <p class="text-green-400 text-sm mb-4">Aspect salvat.</p>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <!-- Texts -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Texte</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-muted mb-1">Titlu site</label>
                        <input type="text" name="site_title" value="<?= e($settings['site_title']) ?>"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-sm text-muted mb-1">Descriere tab browser</label>
                        <input type="text" name="site_tab_description" value="<?= e($settings['site_tab_description'] ?? '') ?>"
                               placeholder="cele mai bune articole de pe internet"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                        <p class="text-xs text-muted mt-1">Apare în tab ca: <em><?= e($settings['site_title']) ?> - [descriere]</em></p>
                    </div>
                    <div>
                        <label class="block text-sm text-muted mb-1">Subtitlu</label>
                        <input type="text" name="site_subtitle" value="<?= e($settings['site_subtitle']) ?>"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-sm text-muted mb-1">Footer</label>
                        <input type="text" name="site_footer" value="<?= e($settings['site_footer']) ?>"
                               class="w-full bg-white border border-muted/20 rounded-lg px-4 py-2 text-txt focus:outline-none focus:border-accent">
                    </div>
                </div>
            </div>

            <!-- Colors -->
            <div class="bg-surface rounded-xl p-6">
                <h2 class="font-semibold mb-4">Culori</h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php
                    $colors = [
                        'color_bg' => 'Background',
                        'color_surface' => 'Surface (carduri)',
                        'color_accent' => 'Accent (butoane)',
                        'color_text' => 'Text principal',
                        'color_text_muted' => 'Text secundar',
                    ];
                    foreach ($colors as $key => $label):
                    ?>
                    <div>
                        <label class="block text-sm text-muted mb-1"><?= $label ?></label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="<?= $key ?>" value="<?= e($settings[$key]) ?>"
                                   class="w-10 h-10 rounded cursor-pointer border-0 bg-transparent">
                            <input type="text" value="<?= e($settings[$key]) ?>" readonly
                                   class="bg-white border border-muted/20 rounded px-2 py-1 text-xs text-muted w-20"
                                   onclick="this.previousElementSibling.click()">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                Salveaza aspectul
            </button>
        </form>
    </main>
</body>
</html>
