<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dataDir = __DIR__ . '/data';

echo "<h3>Setup Articoolat</h3>";

// Create data directory
if (!is_dir($dataDir)) {
    if (mkdir($dataDir, 0755, true)) {
        echo "<p>✅ Director /data/ creat.</p>";
    } else {
        echo "<p>❌ Nu am putut crea /data/</p>";
    }
} else {
    echo "<p>✅ Director /data/ exista deja.</p>";
}

// Check writable
if (is_writable($dataDir)) {
    echo "<p>✅ /data/ este writable.</p>";
} else {
    echo "<p>❌ /data/ NU este writable. Chmod 755 sau 775.</p>";
    chmod($dataDir, 0755);
    echo "<p>Am incercat chmod 755.</p>";
}

// Test SQLite
try {
    $db = new SQLite3($dataDir . '/articoolat.sqlite');
    echo "<p>✅ SQLite functioneaza.</p>";
    $db->close();
} catch (Exception $e) {
    echo "<p>❌ SQLite eroare: " . $e->getMessage() . "</p>";
}

// Test settings
$settingsFile = $dataDir . '/settings.json';
if (!file_exists($settingsFile)) {
    file_put_contents($settingsFile, json_encode(['test' => true]));
    echo "<p>✅ settings.json creat.</p>";
    unlink($settingsFile);
} else {
    echo "<p>✅ settings.json exista.</p>";
}

echo "<p><br><strong>Setup complet. Sterge acest fisier dupa verificare!</strong></p>";
echo "<p><a href='/'>Mergi la site →</a></p>";
