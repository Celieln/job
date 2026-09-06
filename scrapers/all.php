<?php
require_once __DIR__ . '/../database/init.php';
require_once __DIR__ . '/helpers.php';

$scraperFiles = glob(__DIR__ . '/*.php');
foreach ($scraperFiles as $file) {
    $name = basename($file);
    if ($name === 'all.php' || $name === 'helpers.php' || $name === 'headless.php') continue;
    require_once $file;
}

function scrapeAll($source = 'all') {
    $db = getDb();
    
    $apiScrapers = ['remoteok', 'remotive', 'himalayas', 'weworkremotely', 'arbeitnow', 'jobicy'];
    $scrapers = ($source === 'all') ? $apiScrapers : [$source];

    $db->prepare("INSERT INTO scan_logs (scan_type, source, status, started_at) VALUES (?, ?, 'running', datetime('now'))")
       ->execute([$source === 'all' ? 'auto' : 'manual', $source]);
    $scanId = $db->lastInsertId();

    $totalFound = 0;
    $totalAdded = 0;

    // API scrapers
    foreach ($scrapers as $s) {
        $func = 'scrape_' . $s;
        if (function_exists($func)) {
            try {
                $jobs = $func();
                $totalFound += count($jobs);
                foreach ($jobs as $job) {
                    if (empty($job['source_url'])) continue;
                    $stmt = $db->prepare('SELECT id FROM jobs WHERE title = ? AND source = ?');
                    $stmt->execute([$job['title'], $job['source']]);
                    if (!$stmt->fetch()) {
                        $db->prepare("INSERT INTO jobs (source, source_url, title, company, location, country, job_type, salary_min, salary_max, salary_currency, description, tags, posted_at, link_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")->execute([
                            $job['source'], $job['source_url'], $job['title'], $job['company'],
                            $job['location'], $job['country'], $job['job_type'],
                            $job['salary_min'], $job['salary_max'], $job['salary_currency'],
                            $job['description'], $job['tags'], $job['posted_at']
                        ]);
                        $totalAdded++;
                    }
                }
            } catch (Exception $e) {}
        }
    }

    // Headless scrapers (Glints via Puppeteer, JobStreet/Kalibrr/Karir via DDG)
    if (!ENABLE_HEADLESS) {
        $db->prepare("UPDATE scan_logs SET status='completed', jobs_found=?, jobs_added=?, completed_at=datetime('now') WHERE id=?")
           ->execute([$totalFound, $totalAdded, $scanId]);
        return ['totalFound' => $totalFound, 'totalAdded' => $totalAdded];
    }
    
    $headlessSources = ['glints', 'jobstreet', 'kalibrr', 'karir'];
    $runHeadless = ($source === 'all') ? $headlessSources : (in_array($source, $headlessSources) ? [$source] : []);
    
    if (!empty($runHeadless)) {
        try {
            require_once __DIR__ . '/headless.php';
            foreach ($runHeadless as $hs) {
                $jobs = scrape_headless($hs);
                $totalFound += count($jobs);
                foreach ($jobs as $job) {
                    if (empty($job['source_url'])) continue;
                    $stmt = $db->prepare('SELECT id FROM jobs WHERE title = ? AND source = ?');
                    $stmt->execute([$job['title'], $job['source']]);
                    if (!$stmt->fetch()) {
                        $db->prepare("INSERT INTO jobs (source, source_url, title, company, location, country, job_type, salary_min, salary_max, salary_currency, description, tags, posted_at, link_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")->execute([
                            $job['source'], $job['source_url'], $job['title'], $job['company'],
                            $job['location'], $job['country'], $job['job_type'],
                            $job['salary_min'], $job['salary_max'], $job['salary_currency'],
                            $job['description'], $job['tags'], $job['posted_at']
                        ]);
                        $totalAdded++;
                    }
                }
            }
        } catch (Exception $e) {}
    }

    $db->prepare("UPDATE scan_logs SET status='completed', jobs_found=?, jobs_added=?, completed_at=datetime('now') WHERE id=?")
       ->execute([$totalFound, $totalAdded, $scanId]);

    return ['totalFound' => $totalFound, 'totalAdded' => $totalAdded];
}

/**
 * Auto-scan versi cepat: hanya API scrapers (tanpa Puppeteer/DDG)
 * Dipanggil otomatis oleh visitor trigger
 */
function scrapeAllApisOnly() {
    $db = getDb();
    
    $db->prepare("INSERT INTO scan_logs (scan_type, source, status, started_at) VALUES ('auto-api', 'all', 'running', datetime('now'))")->execute();
    $scanId = $db->lastInsertId();

    $apiScrapers = ['remoteok', 'remotive', 'himalayas', 'weworkremotely', 'arbeitnow', 'jobicy'];
    $totalFound = 0;
    $totalAdded = 0;

    foreach ($apiScrapers as $s) {
        $func = 'scrape_' . $s;
        if (!function_exists($func)) continue;
        try {
            $jobs = $func();
            $totalFound += count($jobs);
            foreach ($jobs as $job) {
                if (empty($job['source_url'])) continue;
                $stmt = $db->prepare('SELECT id FROM jobs WHERE title = ? AND source = ?');
                $stmt->execute([$job['title'], $job['source']]);
                if (!$stmt->fetch()) {
                    $db->prepare("INSERT INTO jobs (source, source_url, title, company, location, country, job_type, salary_min, salary_max, salary_currency, description, tags, posted_at, link_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")->execute([
                        $job['source'], $job['source_url'], $job['title'], $job['company'],
                        $job['location'], $job['country'], $job['job_type'],
                        $job['salary_min'], $job['salary_max'], $job['salary_currency'],
                        $job['description'], $job['tags'], $job['posted_at']
                    ]);
                    $totalAdded++;
                }
            }
        } catch (Exception $e) {}
    }

    $db->prepare("UPDATE scan_logs SET status='completed', jobs_found=?, jobs_added=?, completed_at=datetime('now') WHERE id=?")
       ->execute([$totalFound, $totalAdded, $scanId]);

    return ['totalFound' => $totalFound, 'totalAdded' => $totalAdded];
}
