<?php
$settings = load_settings();
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($settings['site_title']) ?></title>
<meta name="description" content="<?= e($settings['site_subtitle']) ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                bg: '<?= e($settings['color_bg']) ?>',
                surface: '<?= e($settings['color_surface']) ?>',
                accent: '<?= e($settings['color_accent']) ?>',
                txt: '<?= e($settings['color_text']) ?>',
                muted: '<?= e($settings['color_text_muted']) ?>',
            }
        }
    }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }
    .vote-btn { transition: all 0.2s ease; }
    .vote-btn:hover { transform: scale(1.15); }
    .vote-btn.voted { color: <?= e($settings['color_accent']) ?>; }
    .card-hover { transition: all 0.15s ease; }
    .card-hover:hover { background: <?= e($settings['color_surface']) ?>; }
</style>
<?php if ($settings['umami_site_id'] && $settings['umami_script_url']): ?>
<script defer src="<?= e($settings['umami_script_url']) ?>" data-website-id="<?= e($settings['umami_site_id']) ?>"></script>
<?php endif; ?>
