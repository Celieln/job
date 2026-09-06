<?php
/**
 * Auto-scan cron - jalankan via cron hosting tiap jam
 * Usage CLI: php /home/zen/www/job/scan_cron.php
 * Usage URL : https://zen.alwaysdata.net/job/scan_cron.php?key=CRON_SECRET
 */

require_once __DIR__ . '/database/init.php';
require_once __DIR__ . '/scrapers/all.php';
require_once __DIR__ . '/api/job_functions.php';

// Proteksi minimal kalau diakses via web (CLI tidak terpengaruh)
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if (!defined('CRON_SECRET') || $key !== CRON_SECRET) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    // Web mode: batasi waktu eksekusi
    set_time_limit(300);
}

echo "[" . date('Y-m-d H:i:s') . "] Auto-scan start\n";

$scanResult = scrapeAll('all');
echo "Scan: found {$scanResult['totalFound']}, added {$scanResult['totalAdded']}\n";

$validated = validateAllJobs(100);
echo "Validate: alive={$validated['alive']}, dead={$validated['dead']}, expired={$validated['expired']}\n";

$cleaned = cleanDeadJobs();
echo "Clean: deactivated {$cleaned['deadDeactivated']} dead\n";

echo "[" . date('Y-m-d H:i:s') . "] Done\n";
