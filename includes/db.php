<?php

function get_db(): SQLite3 {
    $path = __DIR__ . '/../data/articoolat.sqlite';
    $db = new SQLite3($path);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA journal_mode = WAL');

    $db->exec("CREATE TABLE IF NOT EXISTS articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        description TEXT,
        image_url TEXT,
        domain TEXT,
        tag TEXT DEFAULT 'General',
        submitted_by TEXT DEFAULT 'Anonim',
        votes INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now')),
        reading_time INTEGER
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        article_id INTEGER NOT NULL,
        voter_ip TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
        UNIQUE(article_id, voter_ip)
    )");

    // One-time migration: re-fetch og:image for articles missing an image
    $flag = __DIR__ . '/../data/.migrated_images';
    if (!file_exists($flag)) {
        $missing = $db->query("SELECT id, url FROM articles WHERE image_url IS NULL OR image_url = '' OR image_url LIKE '%api.microlink.io%'");
        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'header' => "User-Agent: Mozilla/5.0 (compatible; Articoolat/1.0)\r\n", 'follow_location' => true, 'max_redirects' => 5],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        while ($row = $missing->fetchArray(SQLITE3_ASSOC)) {
            $html = @file_get_contents($row['url'], false, $ctx);
            if ($html && preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                $img = $m[1];
                if (!preg_match('#^https?://#i', $img)) {
                    $parsed = parse_url($row['url']);
                    $img = $parsed['scheme'] . '://' . $parsed['host'] . '/' . ltrim($img, '/');
                }
                $stmt = $db->prepare("UPDATE articles SET image_url = :img WHERE id = :id");
                $stmt->bindValue(':img', $img, SQLITE3_TEXT);
                $stmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
        @file_put_contents($flag, date('Y-m-d H:i:s'));
    }

    return $db;
}
