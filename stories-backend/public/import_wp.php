<?php
/**
 * Browser‐triggered WordPress migration importer.
 * 
 * Place this file under public/ then visit:
 * https://YOUR_DOMAIN/admin/import_wp.php
 * to run the Node import script without SSH.
 */

set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Running WordPress Migration Import</h1>";

$migrationDir = realpath(__DIR__ . '/../_wp migration');
if (! $migrationDir) {
    echo "<p style='color:red;'>Migration directory not found.</p>";
    exit;
}

// Install Node dependencies (only on first run)
echo "<h2>Installing dependencies…</h2><pre>";
passthru("cd " . escapeshellarg($migrationDir) . " && npm install --no-audit 2>&1", $installExit);
echo "</pre>";
if ($installExit !== 0) {
    echo "<p style='color:red;'>npm install failed (exit code $installExit).</p>";
    exit;
}

// Run the import script
echo "<h2>Running import.mjs…</h2><pre>";
passthru("cd " . escapeshellarg($migrationDir) . " && node import.mjs 2>&1", $importExit);
echo "</pre>";

echo "<p><strong>Import script exited with code: $importExit</strong></p>";
echo "<p>Now log in at <a href='/admin/stories'>/admin/stories</a> to verify source_type and allow_reviews.</p>";
echo "<p>Trigger Netlify rebuild with:<br>
<code>curl -X POST -d '{}' https://api.netlify.com/build_hooks/your-hook-id</code></p>";