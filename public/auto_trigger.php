<?php
/**
 * AUTO-SCAN TRIGGER - Poor man's cron
 * Dipanggil otomatis setiap ada visitor buka dashboard
 * Kalau sudah > 30 menit sejak scan terakhir, jalankan scan baru
 * Jalan di SHARED HOSTING tanpa perlu akses cron!
 */

require_once __DIR__ . '/../database/init.php';

function shouldAutoScan($intervalMinutes = 30) {
    $db = getDb();
    $stmt = $db->query("SELECT started_at FROM scan_logs WHERE status = 'completed' ORDER BY started_at DESC LIMIT 1");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$last) return true; // belum pernah scan
    
    $lastTime = strtotime($last['started_at']);
    $elapsed = (time() - $lastTime) / 60;
    
    return $elapsed >= $intervalMinutes;
}

function triggerBackgroundScan() {
    // Async background call ke API scan endpoint
    $url = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/job/public/')) . '/api/admin/auto-scan';
    
    // Fire-and-forget pakai fsockopen biar tidak blocking
    $parts = parse_url($url);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 80;
    $path = $parts['path'] ?? '/job/api/admin/auto-scan';
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 3);
    if ($fp) {
        $out = "POST $path HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Content-Type: application/json\r\n";
        $out .= "Content-Length: 2\r\n";
        $out .= "Connection: Close\r\n\r\n{}";
        fwrite($fp, $out);
        fclose($fp);
        return true;
    }
    return false;
}

// Jalankan kalau perlu
if (shouldAutoScan(30)) {
    triggerBackgroundScan();
}
