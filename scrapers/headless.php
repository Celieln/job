<?php
function scrape_headless($source = 'all') {
    $jobs = [];
    
    // Glints via Puppeteer stealth
    if ($source === 'all' || $source === 'glints') {
        $result = runNodeScript(__DIR__ . '/../scrape_glints_final.js');
        $jobs = array_merge($jobs, $result);
    }
    
    // JobStreet/Kalibrr/Karir via DuckDuckGo
    if ($source === 'all' || in_array($source, ['jobstreet', 'kalibrr', 'karir'])) {
        $result = runNodeScript(__DIR__ . '/../scrape_via_ddg.js');
        $jobs = array_merge($jobs, $result);
    }
    
    return $jobs;
}

function runNodeScript($scriptPath) {
    if (!file_exists($scriptPath)) return [];
    $cmd = 'node ' . escapeshellarg($scriptPath) . ' 2>&1';
    $output = shell_exec($cmd);
    if (!$output) return [];
    $jobs = json_decode($output, true);
    return is_array($jobs) ? $jobs : [];
}
