<?php
$settings = load_settings();
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($settings['site_title']) ?><?= !empty($settings['site_tab_description']) ? ' - ' . e($settings['site_tab_description']) : '' ?></title>
<meta name="description" content="<?= e($settings['site_subtitle']) ?>">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="shortcut icon" href="/assets/favicon.svg">
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
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    @font-face {
        font-family: 'Bristol';
        src: url('/assets/fonts/bristol.woff2') format('woff2'),
             url('/assets/fonts/bristol.woff') format('woff');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    body { font-family: 'Nunito', sans-serif; }
    .font-bristol { font-family: 'Bristol', 'DM Serif Display', serif; letter-spacing: -0.03em; }
    .vote-btn { transition: all 0.2s ease; color: <?= e($settings['color_text_muted']) ?>; }
    .vote-btn:hover { transform: scale(1.15); color: <?= e($settings['color_accent']) ?>; }
    .vote-btn.voted { color: <?= e($settings['color_accent']) ?>; }
    .card-hover { transition: all 0.15s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.10); }
    #liveDesc { font-size: <?= e($settings['desc_size_desktop'] ?? '16') ?>px; }
    #liveNlTitle { font-size: <?= e($settings['nl_title_size_desktop'] ?? '18') ?>px; }
    #liveNlDesc { font-size: <?= e($settings['nl_desc_size_desktop'] ?? '14') ?>px; }
    @media (max-width: 640px) {
        #liveDesc { font-size: <?= e($settings['desc_size_mobile'] ?? '15') ?>px; }
        #liveNlTitle { font-size: <?= e($settings['nl_title_size_mobile'] ?? '16') ?>px; }
        #liveNlDesc { font-size: <?= e($settings['nl_desc_size_mobile'] ?? '13') ?>px; }
    }
</style>
<?php if (!empty($settings['ga_script'])): ?>
<?= $settings['ga_script'] ?>
<?php endif; ?>
