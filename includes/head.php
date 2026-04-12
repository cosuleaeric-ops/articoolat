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
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    @font-face {
        font-family: 'Bristol';
        src: url('/assets/fonts/bristol.woff2') format('woff2'),
             url('/assets/fonts/bristol.woff') format('woff');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    body { font-family: 'Inter', sans-serif; }
    .font-bristol { font-family: 'Bristol', 'DM Serif Display', serif; letter-spacing: -0.03em; }
    .vote-btn { transition: all 0.2s ease; }
    .vote-btn:hover { transform: scale(1.15); }
    .vote-btn.voted { color: <?= e($settings['color_accent']) ?>; }
    .card-hover { transition: all 0.15s ease; }
    .card-hover:hover { background: <?= e($settings['color_surface']) ?>; }
</style>
<?php if (!empty($settings['ga_script'])): ?>
<?= $settings['ga_script'] ?>
<?php endif; ?>
