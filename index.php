<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/admin/auth.php';
$is_admin = is_authenticated();

$db = get_db();
$settings = load_settings();

$tab = $_GET['tab'] ?? 'new';
$top_period = $_GET['period'] ?? 'all';

// Build query based on tab
switch ($tab) {
    case 'new':
        $new_days = max(1, (int)($settings['new_days'] ?? 7));
        $sql = "SELECT * FROM articles WHERE created_at >= datetime('now', '-{$new_days} days') ORDER BY created_at DESC LIMIT 50";
        break;
    case 'top':
        $sql = "SELECT * FROM articles ORDER BY votes DESC LIMIT 50";
        break;
    default: // hot
        $tab = 'hot';
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 200";
        break;
}

$result = $db->query($sql);
$articles = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($tab === 'hot') {
        $row['_score'] = hot_score($row['votes'], $row['created_at']);
    }
    $articles[] = $row;
}

if ($tab === 'hot') {
    usort($articles, fn($a, $b) => $b['_score'] <=> $a['_score']);
    $articles = array_slice($articles, 0, 50);
}

// Check which articles user already voted on
$ip = get_client_ip();
$voted_ids = [];
$stmt = $db->prepare("SELECT article_id FROM votes WHERE voter_ip = :ip");
$stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$vr = $stmt->execute();
while ($v = $vr->fetchArray(SQLITE3_ASSOC)) {
    $voted_ids[$v['article_id']] = true;
}
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
            <button onclick="document.getElementById('editPanel').classList.toggle('translate-x-full')"
                    class="hover:underline transition-colors">Editează live</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Bar (floating) -->
    <div class="sticky z-50 pt-3 px-4 <?= $is_admin ? 'top-[28px]' : 'top-0' ?>">
        <nav class="max-w-[36rem] mx-auto bg-surface/95 backdrop-blur-sm shadow-[0_2px_12px_rgba(0,0,0,0.1)] rounded-xl px-5 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <svg width="18" height="24" viewBox="0 0 40 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 4h32a2 2 0 0 1 2 2v42L20 38 2 48V6a2 2 0 0 1 2-2z" fill="<?= e($settings['color_accent']) ?>"/>
                    <path d="M13 20h14M13 27h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <span class="text-xl font-bold tracking-tight"><?= e($settings['site_title']) ?></span>
            </a>
            <a href="/submit.php" class="bg-accent text-white px-4 py-1.5 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                + Articol nou
            </a>
        </nav>
    </div>

    <!-- Header -->
    <header class="max-w-[36rem] mx-auto px-4 pt-8 pb-4">
        <h2 id="liveHeading" class="leading-tight tracking-tight text-center text-txt" style="font-size: 34px; font-weight: 800;"><?= e($settings['site_subtitle']) ?></h2>
        <p id="liveDesc" class="text-muted mt-6 text-center">Noi le-am adunat pentru tine. <span style="text-decoration: underline; text-decoration-color: <?= e($settings['color_accent']) ?>; text-underline-offset: 3px;">Adaugă-ți și tu</span> articolele preferate, și <span style="text-decoration: underline; text-decoration-color: <?= e($settings['color_accent']) ?>; text-underline-offset: 3px;">lasă un like</span> celor mai bune.</p>

        <!-- Tabs -->
        <nav class="flex gap-2 mt-10 border-b border-muted/20">
            <a href="/?tab=new"
               class="px-5 py-3 text-base font-semibold border-b-2 transition-colors <?= $tab === 'new' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                <svg class="inline w-4 h-4 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg> Noi
            </a>
            <a href="/?tab=top"
               class="px-5 py-3 text-base font-semibold border-b-2 transition-colors <?= $tab === 'top' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-txt' ?>">
                <svg class="inline w-4 h-4 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> Top
            </a>
        </nav>

    </header>

    <!-- Articles -->
    <main class="max-w-[36rem] mx-auto px-4 pb-12">
        <?php if (empty($articles)): ?>
        <div class="text-center py-16">
            <p class="text-muted text-lg">Niciun articol inca.</p>
            <a href="/submit.php" class="text-accent hover:underline mt-2 inline-block">Fii primul care adauga unul!</a>
        </div>
        <?php endif; ?>

        <?php foreach ($articles as $i => $article): ?>
        <?php if ($i === 3): ?>
        <!-- Email subscription -->
        <div class="my-4 rounded-xl px-6 py-7" style="background-color: <?= e($settings['color_accent']) ?>">
            <p id="liveNlTitle" class="font-bold text-white">Vrei să primești articolele direct pe email?</p>
            <p id="liveNlDesc" class="text-white/80 mt-1">Abonează-te la newsletter ca să primești săptămânal cele mai bune 3 articole regăsite pe Articoolat.</p>
            <form id="emailSubForm" class="flex gap-2 mt-4">
                <input type="email" name="email" required placeholder=""
                       class="flex-1 rounded-lg px-3 py-2.5 text-sm text-txt bg-white focus:outline-none">
                <button type="submit" class="bg-white/20 hover:bg-white/30 text-white font-semibold text-sm px-5 py-2.5 rounded-lg transition-colors">
                    Abonează-te
                </button>
            </form>
            <p id="emailSubMsg" class="text-white/90 text-xs mt-2 hidden"></p>
        </div>
        <?php endif; ?>
        <?php
        $thumb = $article['image_url'] ?: '';
        ?>
        <article class="card-hover flex items-center gap-4 p-4 rounded-xl bg-surface shadow-[0_1px_3px_rgba(0,0,0,0.08)] mb-3">
            <!-- Vote button -->
            <div class="flex flex-col items-center w-10 flex-shrink-0">
                <button onclick="vote(<?= $article['id'] ?>, this)"
                        class="vote-btn <?= isset($voted_ids[$article['id']]) ? 'voted' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                </button>
                <span class="text-sm font-semibold mt-0.5 vote-count"><?= $article['votes'] ?></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <a href="<?= e($article['url']) ?>" target="_blank" rel="noopener"
                   class="text-txt font-medium hover:text-accent transition-colors leading-snug line-clamp-2">
                    <?= e($article['title']) ?>
                </a>
                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-muted">
                    <span class="bg-muted/10 px-2 py-0.5 rounded"><?= e($article['domain']) ?></span>
                    <?php if ($article['reading_time']): ?>
                    <span>·</span>
                    <span class="inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><?= $article['reading_time'] ?> min</span>
                    <?php endif; ?>
                    <span>·</span>
                    <span><?= e($article['submitted_by']) ?></span>
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="w-14 h-14 flex-shrink-0 rounded-lg overflow-hidden bg-muted/10">
                <?php if ($thumb): ?>
                <img src="<?= e($thumb) ?>" alt="" loading="lazy"
                     class="w-14 h-14 object-cover"
                     onerror="this.parentElement.innerHTML='<div class=\'w-14 h-14 flex items-center justify-center bg-accent/10 text-accent font-bold text-lg\'><?= strtoupper(mb_substr($article['domain'], 0, 1)) ?></div>'">
                <?php else: ?>
                <div class="w-14 h-14 flex items-center justify-center bg-accent/10 text-accent font-bold text-lg">
                    <?= strtoupper(mb_substr($article['domain'], 0, 1)) ?>
                </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if (!empty($articles)): ?>
        <div class="text-center py-10">
            <p class="text-muted text-sm mb-4">Atât, momentan...</p>
            <a href="/submit.php" class="inline-block bg-accent text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity">
                + Adaugă un articol
            </a>
        </div>
        <?php endif; ?>
    </main>

    <?php if ($is_admin): ?>
    <!-- Edit Panel -->
    <div id="editPanel" class="fixed top-0 right-0 z-50 h-full w-80 bg-surface shadow-2xl transform translate-x-full transition-transform duration-300 overflow-y-auto">
        <div class="p-5">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg">Editează live</h3>
                <button onclick="document.getElementById('editPanel').classList.add('translate-x-full')" class="text-muted hover:text-txt text-xl">&times;</button>
            </div>
            <form id="editForm" class="space-y-4">
                <div>
                    <label class="block text-xs text-muted mb-1">Titlu site</label>
                    <input type="text" name="site_title" value="<?= e($settings['site_title']) ?>"
                           oninput="document.title=this.value"
                           class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1">Heading principal</label>
                    <textarea name="site_subtitle" rows="2"
                              oninput="document.getElementById('liveHeading').textContent=this.value"
                              class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent resize-none"><?= e($settings['site_subtitle']) ?></textarea>
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1">Footer</label>
                    <input type="text" name="site_footer" value="<?= e($settings['site_footer']) ?>"
                           oninput="document.getElementById('liveFooter').textContent=this.value"
                           class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                </div>
                <hr class="border-muted/20">
                <h4 class="font-semibold text-sm">Culori</h4>
                <?php
                $color_fields = [
                    'color_bg' => 'Background',
                    'color_surface' => 'Carduri',
                    'color_accent' => 'Accent',
                    'color_text' => 'Text',
                    'color_text_muted' => 'Text secundar',
                ];
                $color_css_map = [
                    'color_bg' => '--live-bg',
                    'color_surface' => '--live-surface',
                    'color_accent' => '--live-accent',
                    'color_text' => '--live-text',
                    'color_text_muted' => '--live-muted',
                ];
                foreach ($color_fields as $key => $label):
                ?>
                <div class="flex items-center gap-2">
                    <input type="color" value="<?= e($settings[$key]) ?>" class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent"
                           oninput="this.nextElementSibling.value=this.value; liveColor('<?= $key ?>', this.value)">
                    <input type="text" name="<?= $key ?>" value="<?= e($settings[$key]) ?>" maxlength="7"
                           class="w-20 bg-bg border border-muted/20 rounded px-2 py-1 text-xs font-mono focus:outline-none focus:border-accent"
                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){this.previousElementSibling.value=this.value; liveColor('<?= $key ?>', this.value)}">
                    <span class="text-xs text-muted"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
                <hr class="border-muted/20">
                <h4 class="font-semibold text-sm">Tipografie</h4>
                <!-- Device tabs -->
                <div class="flex rounded-lg overflow-hidden border border-muted/20 text-xs">
                    <button type="button" id="tabDesktop" onclick="switchTypoTab('desktop')"
                            class="flex-1 py-1.5 font-medium bg-accent text-white transition-colors">Desktop</button>
                    <button type="button" id="tabMobile" onclick="switchTypoTab('mobile')"
                            class="flex-1 py-1.5 font-medium text-muted hover:text-txt transition-colors">Mobile</button>
                </div>
                <div id="typoDesktop" class="space-y-3">
                    <div>
                        <label class="block text-xs text-muted mb-1">Heading (px)</label>
                        <input type="number" name="heading_size" value="<?= e($settings['heading_size'] ?? '34') ?>" min="20" max="70"
                               oninput="document.getElementById('liveHeading').style.fontSize=this.value+'px'"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-xs text-muted mb-1">Descriere heading (px)</label>
                        <input type="number" name="desc_size_desktop" value="<?= e($settings['desc_size_desktop'] ?? '16') ?>" min="10" max="30"
                               oninput="document.getElementById('liveDesc').style.fontSize=this.value+'px'"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-xs text-muted mb-1">Titlu newsletter (px)</label>
                        <input type="number" name="nl_title_size_desktop" value="<?= e($settings['nl_title_size_desktop'] ?? '18') ?>" min="10" max="30"
                               oninput="document.getElementById('liveNlTitle').style.fontSize=this.value+'px'"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-xs text-muted mb-1">Descriere newsletter (px)</label>
                        <input type="number" name="nl_desc_size_desktop" value="<?= e($settings['nl_desc_size_desktop'] ?? '14') ?>" min="10" max="30"
                               oninput="document.getElementById('liveNlDesc').style.fontSize=this.value+'px'"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                </div>
                <div id="typoMobile" class="space-y-3 hidden">
                    <div>
                        <label class="block text-xs text-muted mb-1">Descriere heading (px)</label>
                        <input type="number" name="desc_size_mobile" value="<?= e($settings['desc_size_mobile'] ?? '15') ?>" min="10" max="30"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-xs text-muted mb-1">Titlu newsletter (px)</label>
                        <input type="number" name="nl_title_size_mobile" value="<?= e($settings['nl_title_size_mobile'] ?? '16') ?>" min="10" max="30"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-xs text-muted mb-1">Descriere newsletter (px)</label>
                        <input type="number" name="nl_desc_size_mobile" value="<?= e($settings['nl_desc_size_mobile'] ?? '13') ?>" min="10" max="30"
                               class="w-full bg-bg border border-muted/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-accent">
                    </div>
                </div>
                <button type="submit" id="saveBtn" class="w-full bg-accent text-white py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
                    Salvează
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="max-w-[36rem] mx-auto px-4 py-8 border-t border-muted/20">
        <p id="liveFooter" class="text-center text-muted text-sm"><?= e($settings['site_footer']) ?></p>
    </footer>

    <script>
    document.getElementById('emailSubForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('emailSubMsg');
        const email = e.target.email.value.trim();
        try {
            const res = await fetch('/api/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            const data = await res.json();
            msg.textContent = data.success ? 'Te-ai abonat cu succes!' : (data.error || 'Eroare la abonare.');
            msg.classList.remove('hidden');
            if (data.success) e.target.reset();
        } catch {
            msg.textContent = 'Eroare de rețea.';
            msg.classList.remove('hidden');
        }
    });

    async function vote(articleId, btn) {
        try {
            const res = await fetch('/api/vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId })
            });
            const data = await res.json();

            if (data.success) {
                if (data.voted) {
                    btn.classList.add('voted');
                } else {
                    btn.classList.remove('voted');
                }
                btn.nextElementSibling.textContent = data.votes;
            }
        } catch (err) {
            console.error('Vote failed:', err);
        }
    }
    const colorMap = {
        color_bg: 'bg', color_surface: 'surface',
        color_accent: 'accent', color_text: 'txt',
        color_text_muted: 'muted'
    };
    function liveColor(key, val) {
        const twName = colorMap[key];
        if (!twName) return;
        const style = document.getElementById('liveStyles') || (() => {
            const s = document.createElement('style');
            s.id = 'liveStyles';
            document.head.appendChild(s);
            return s;
        })();
        if (!liveColor._colors) liveColor._colors = {};
        liveColor._colors[twName] = val;
        let css = '';
        for (const [name, color] of Object.entries(liveColor._colors)) {
            if (name === 'bg') css += `.bg-bg { background-color: ${color} !important; }\n`;
            else if (name === 'surface') css += `.bg-surface, .bg-surface\\/95 { background-color: ${color} !important; }\n`;
            else if (name === 'accent') css += `.text-accent, .border-accent { color: ${color} !important; } .bg-accent { background-color: ${color} !important; } .vote-btn.voted, .vote-btn:hover { color: ${color} !important; }\n`;
            else if (name === 'txt') css += `.text-txt { color: ${color} !important; } .bg-txt { background-color: ${color} !important; }\n`;
            else if (name === 'muted') css += `.text-muted { color: ${color} !important; } .vote-btn { color: ${color} !important; } .vote-btn.voted, .vote-btn:hover { color: ${liveColor._colors['accent'] || ''} !important; }\n`;
        }
        style.textContent = css;
    }

    function switchTypoTab(tab) {
        const isDesktop = tab === 'desktop';
        document.getElementById('typoDesktop').classList.toggle('hidden', !isDesktop);
        document.getElementById('typoMobile').classList.toggle('hidden', isDesktop);
        document.getElementById('tabDesktop').className = 'flex-1 py-1.5 font-medium transition-colors ' + (isDesktop ? 'bg-accent text-white' : 'text-muted hover:text-txt');
        document.getElementById('tabMobile').className = 'flex-1 py-1.5 font-medium transition-colors ' + (!isDesktop ? 'bg-accent text-white' : 'text-muted hover:text-txt');
    }

    document.getElementById('editForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        const form = new FormData(e.target);
        const body = Object.fromEntries(form.entries());
        btn.textContent = 'Se salvează...';
        btn.disabled = true;
        const res = await fetch('/api/update-appearance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = 'Salvat!';
            setTimeout(() => { btn.textContent = 'Salvează'; btn.disabled = false; }, 1500);
        } else {
            alert(data.error || 'Eroare');
            btn.textContent = 'Salvează';
            btn.disabled = false;
        }
    });
    </script>
</body>
</html>
