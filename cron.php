<?php
/**
 * Cron script for auto-cleaning dead/old jobs
 * Run with: php cron.php
 * Or schedule via Windows Task Scheduler
 */

require_once __DIR__ . '/database/init.php';
require_once __DIR__ . '/scrapers/all.php';

$db = getDb();

echo "🧹 Running clean cycle...\n";

// Validate pending jobs
$stmt = $db->query("SELECT id, source_url FROM jobs WHERE link_status = 'pending' LIMIT 100");
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

$alive = $dead = $expired = 0;
foreach ($pending as $job) {
    $ch = curl_init($job['source_url']);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = 'alive';
    if (in_array($code, [404, 410, 403, 503])) {
        $status = 'dead';
        $dead++;
    } elseif ($code >= 200 && $code < 400) {
        $alive++;
    } else {
        $status = 'dead';
        $dead++;
    }

    $db->prepare("UPDATE jobs SET link_status = ?, last_validated = datetime('now') WHERE id = ?")
       ->execute([$status, $job['id']]);
}

echo "  Validated: " . count($pending) . " (alive: $alive, dead: $dead)\n";

// Remove dead jobs
$deadCount = $db->query("SELECT COUNT(*) as c FROM jobs WHERE link_status IN ('dead','expired')")->fetch()['c'];
$db->exec("DELETE FROM jobs WHERE link_status IN ('dead','expired')");
echo "  Removed $deadCount dead jobs\n";

// Remove old jobs
$oldCount = $db->query("SELECT COUNT(*) as c FROM jobs WHERE scraped_at < datetime('now', '-" . MAX_AGE_DAYS . " days') AND is_active = 1")->fetch()['c'];
$db->exec("DELETE FROM jobs WHERE scraped_at < datetime('now', '-" . MAX_AGE_DAYS . " days') AND is_active = 1");
echo "  Removed $oldCount old jobs (>" . MAX_AGE_DAYS . " days)\n";

$total = $db->query("SELECT COUNT(*) as c FROM jobs WHERE is_active = 1")->fetch()['c'];
echo "  Active jobs remaining: $total\n";
echo "✅ Clean cycle complete\n";
