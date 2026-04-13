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

    // One-time cleanup: clear broken Microlink URLs from old articles
    $db->exec("UPDATE articles SET image_url = NULL WHERE image_url LIKE '%api.microlink.io%'");

    return $db;
}
