<?php
require_once __DIR__ . '/database/init.php';
$jsonFile = $argv[1] ?? __DIR__ . '/google_jobs.json';
if (!file_exists($jsonFile)) { echo "No file\n"; exit; }
$raw = file_get_contents($jsonFile);
if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3); // remove BOM
$jobs = json_decode($raw, true);
if (!$jobs) { echo "Invalid JSON\n"; exit; }

$db = getDb();
$added = 0;
foreach ($jobs as $job) {
    $stmt = $db->prepare('SELECT id FROM jobs WHERE title = ? AND source = ?');
    $stmt->execute([$job['title'], $job['source']]);
    if (!$stmt->fetch()) {
        $db->prepare("INSERT INTO jobs (source, source_url, title, company, location, country, job_type, salary_min, salary_max, salary_currency, description, tags, posted_at, link_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")->execute([
            $job['source'], $job['source_url'], $job['title'], $job['company'],
            $job['location'], $job['country'], $job['job_type'],
            $job['salary_min'], $job['salary_max'], $job['salary_currency'],
            $job['description'], $job['tags'], $job['posted_at']
        ]);
        $added++;
    }
}
echo "Imported $added new jobs from " . count($jobs) . " total\n";
