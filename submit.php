<?php
require_once __DIR__ . '/includes/functions.php';
$settings = load_settings();
$tags = get_tags();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="bg-bg text-txt min-h-screen">

    <header class="max-w-[36rem] mx-auto px-4 pt-8 pb-6">
        <a href="/" class="text-muted hover:text-txt text-sm transition-colors">← Inapoi</a>
        <h1 class="text-2xl font-bold mt-4">Adauga un articol</h1>
    </header>

    <main class="max-w-[36rem] mx-auto px-4 pb-12">
        <form id="submitForm" class="space-y-5">

            <!-- URL -->
            <div>
                <label class="block text-sm font-medium mb-1.5">Link articol *</label>
                <input type="url" name="url" id="urlInput" required placeholder=""
                       class="w-full bg-surface border border-muted/20 rounded-lg px-4 py-3 text-txt placeholder-muted focus:outline-none focus:border-accent transition-colors">
                <p id="fetchStatus" class="text-xs text-muted mt-1 hidden">Se incarca metadata...</p>
            </div>

            <!-- Title (hidden, auto-filled) -->
            <input type="hidden" name="title" id="titleInput">

            <!-- Description (hidden, auto-filled) -->
            <input type="hidden" name="description" id="descInput">

            <!-- Image URL (hidden, auto-filled) -->
            <input type="hidden" name="image_url" id="imageInput">

            <!-- Reading time (hidden, auto-filled) -->
            <input type="hidden" name="reading_time" id="readingTimeInput">

            <!-- Preview image -->
            <div id="imagePreview" class="hidden">
                <label class="block text-sm font-medium mb-1.5">Preview imagine</label>
                <img id="previewImg" src="" alt="" class="w-full max-h-48 object-cover rounded-lg bg-surface">
            </div>

            <!-- Username -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-medium">Username *</label>
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
                if (data.description) document.getElementById('descInput').value = data.description;
                if (data.image) {
                    document.getElementById('imageInput').value = data.image;
                    document.getElementById('previewImg').src = data.image;
                    document.getElementById('imagePreview').classList.remove('hidden');
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
