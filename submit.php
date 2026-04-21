<?php
require_once __DIR__ . '/includes/functions.php';
$settings = load_settings();
$tags = get_tags();
$is_admin = is_authenticated();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen">

    <?php if ($is_admin): ?>
    <!-- Admin Top Bar -->
    <div class="sticky top-0 z-40 bg-txt text-bg text-xs py-1.5">
        <div class="max-w-[36rem] mx-auto px-4 flex items-center justify-between">
            <a href="/admin/" class="hover:underline">Panou admin →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Bar -->
    <div class="sticky z-50 pt-3 px-4 <?= $is_admin ? 'top-[28px]' : 'top-0' ?>">
        <nav class="max-w-[36rem] mx-auto bg-surface/95 backdrop-blur-sm shadow-[0_2px_12px_rgba(0,0,0,0.1)] rounded-xl px-5 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <svg width="18" height="24" viewBox="0 0 40 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 4h32a2 2 0 0 1 2 2v42L20 38 2 48V6a2 2 0 0 1 2-2z" fill="<?= e($settings['color_accent']) ?>"/>
                    <path d="M13 20h14M13 27h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <span class="text-xl font-bold tracking-tight"><?= e($settings['site_title']) ?></span>
            </a>
            <a href="/submit" class="bg-accent text-white px-4 py-1.5 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                + Articol nou
            </a>
        </nav>
    </div>

    <header class="max-w-[36rem] mx-auto px-4 pt-8 pb-6">
        <h1 class="text-2xl font-bold">Adauga un articol</h1>
    </header>

    <main class="max-w-[36rem] mx-auto px-4 pb-12">
        <form id="submitForm" class="space-y-5">

            <!-- URL -->
            <div>
                <label class="block text-sm font-medium mb-1.5">Link articol <span class="text-red-500">*</span></label>
                <input type="url" name="url" id="urlInput" required placeholder=""
                       class="w-full bg-surface border border-muted/20 rounded-lg px-4 py-3 text-txt placeholder-muted focus:outline-none focus:border-accent transition-colors">
                <p id="fetchStatus" class="text-xs text-muted mt-1 hidden">Se incarca metadata...</p>
            </div>

            <!-- Title (hidden, auto-filled) -->
            <input type="hidden" name="title" id="titleInput">

            <!-- Image URL (hidden, auto-filled) -->
            <input type="hidden" name="image_url" id="imageInput">

            <!-- Reading time (hidden, auto-filled) -->
            <input type="hidden" name="reading_time" id="readingTimeInput">

            <!-- Username -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-medium">Username <span class="text-red-500">*</span></label>
                    <span class="text-xs text-muted flex items-center gap-1">
                        Nu ai nevoie de cont
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-muted/20 text-muted text-[10px] font-bold cursor-default" title="Poti adauga articole fara a-ti crea un cont.">i</span>
                    </span>
                </div>
                <input type="text" name="submitted_by" required placeholder=""
                       class="w-full bg-surface border border-muted/20 rounded-lg px-4 py-3 text-txt placeholder-muted focus:outline-none focus:border-accent transition-colors">
            </div>

            <!-- Submit -->
            <button type="submit" id="submitBtn"
                    class="w-full bg-accent text-white py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                Adauga articol
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>

            <p id="formError" class="text-red-400 text-sm hidden"></p>
            <p id="formSuccess" class="text-green-400 text-sm hidden"></p>
        </form>
    </main>

    <script>
    const urlInput = document.getElementById('urlInput');
    let fetchTimeout;

    urlInput.addEventListener('input', () => {
        clearTimeout(fetchTimeout);
        const url = urlInput.value.trim();
        if (!url.startsWith('http')) return;

        fetchTimeout = setTimeout(() => fetchMeta(url), 500);
    });

    async function fetchMeta(url) {
        const status = document.getElementById('fetchStatus');
        status.classList.remove('hidden');
        status.textContent = 'Se incarca metadata...';

        try {
            const res = await fetch('/api/fetch-meta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url })
            });
            const data = await res.json();

            if (data.success) {
                if (data.title) document.getElementById('titleInput').value = data.title;
                if (data.image) {
                    document.getElementById('imageInput').value = data.image;
                }
                if (data.reading_time) {
                    document.getElementById('readingTimeInput').value = data.reading_time;
                }
                status.textContent = 'Metadata incarcata!';
            } else {
                status.textContent = 'Nu am putut extrage metadata. Completeaza manual.';
            }
        } catch (err) {
            status.textContent = 'Eroare la incarcarea metadata.';
        }
    }

    document.getElementById('submitForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const errorEl = document.getElementById('formError');
        const successEl = document.getElementById('formSuccess');

        errorEl.classList.add('hidden');
        successEl.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Se trimite...';

        const formData = new FormData(e.target);
        const body = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('/api/submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();

            if (data.success) {
                successEl.textContent = 'Articol adaugat cu succes!';
                successEl.classList.remove('hidden');
                setTimeout(() => window.location.href = '/', 1000);
            } else {
                errorEl.textContent = data.error || 'Eroare la trimitere.';
                errorEl.classList.remove('hidden');
            }
        } catch (err) {
            errorEl.textContent = 'Eroare de retea.';
            errorEl.classList.remove('hidden');
        }

        btn.disabled = false;
        btn.innerHTML = 'Adauga articol <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';
    });
    </script>
</body>
</html>
