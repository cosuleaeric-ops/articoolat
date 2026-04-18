<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$nav_items = [
    'index' => ['label' => 'Dashboard', 'icon' => '📊'],
    'articles' => ['label' => 'Articole', 'icon' => '📝'],
    'appearance' => ['label' => 'Aspect', 'icon' => '🎨'],
    'settings' => ['label' => 'Setari', 'icon' => '⚙️'],
];
?>
<aside class="w-56 bg-surface min-h-screen p-4 flex flex-col border-r border-muted/20">
    <div class="mb-6">
        <h2 class="font-bold text-lg"><?= e($settings['site_title'] ?? 'Articoolat') ?> <span class="text-muted text-sm font-normal">— Admin</span></h2>
        <a href="/" class="text-xs text-accent hover:underline mt-1 inline-block">🟢 Vezi site</a>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
        <?php foreach ($nav_items as $page => $item): ?>
        <a href="/admin/<?= $page === 'index' ? '' : $page . '.php' ?>"
           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors <?= $current_page === $page ? 'bg-accent/20 text-accent font-medium' : 'text-muted hover:text-txt hover:bg-muted/10' ?>">
            <span><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <a href="/admin/?logout=1" class="text-muted hover:text-txt text-sm px-3 py-2 mt-4">Deconecteaza-te</a>
</aside>
