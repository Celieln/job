<?php
require_once __DIR__ . '/database/init.php';
require_once __DIR__ . '/scrapers/helpers.php';
require_once __DIR__ . '/scrapers/himalayas.php';
$jobs = scrape_himalayas();
$db = getDb();
$added = 0;
foreach ($jobs as $j) {
    $s = $db->prepare('SELECT id FROM jobs WHERE title = ? AND source = ?');
    $s->execute([$j['title'], $j['source']]);
    if (!$s->fetch()) {
        $db->prepare("INSERT INTO jobs(source,source_url,title,company,location,country,job_type,salary_min,salary_max,salary_currency,description,tags,posted_at,link_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')")
           ->execute([$j['source'], $j['source_url'], $j['title'], $j['company'], $j['location'], $j['country'], $j['job_type'], $j['salary_min'], $j['salary_max'], $j['salary_currency'], $j['description'], $j['tags'], $j['posted_at']]);
        $added++;
    }
}
echo "Himalayas: $added new from " . count($jobs) . "\n";
