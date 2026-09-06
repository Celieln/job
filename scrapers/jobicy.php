<?php
function scrape_jobicy() {
    $jobs = [];
    try {
        $json = @file_get_contents('https://jobicy.com/api/v2/remote-jobs?count=50&geo=indonesia');
        if (!$json) $json = @file_get_contents('https://jobicy.com/api/v2/remote-jobs?count=50');
        if (!$json) return [];
        $data = json_decode($json, true);
        foreach (($data['jobs'] ?? []) as $j) {
            $location = $j['jobGeo'] ?? $j['jobRegion'] ?? 'Remote';
            $jobs[] = [
                'source' => 'jobicy', 'source_url' => $j['url'] ?? '',
                'title' => $j['jobTitle'] ?? '', 'company' => $j['companyName'] ?? 'Various',
                'location' => $location, 'country' => detectCountry($location),
                'job_type' => $j['jobType'] ?? 'fulltime',
                'salary_min' => $j['annualSalaryMin'] ?? null, 'salary_max' => $j['annualSalaryMax'] ?? null,
                'salary_currency' => $j['salaryCurrency'] ?? 'USD',
                'description' => strip_tags(substr($j['jobDescription'] ?? '', 0, 500)),
                'tags' => json_encode(['jobicy']), 'posted_at' => $j['pubDate'] ?? date('Y-m-d H:i:s'),
            ];
        }
    } catch (Exception $e) {}
    return $jobs;
}

function scrape_devitjobs() {
    $jobs = [];
    try {
        $json = @file_get_contents('https://devitjobs.com/api/feed.json');
        if (!$json) return [];
        $data = json_decode($json, true);
        foreach (($data['jobs'] ?? $data ?? []) as $j) {
            if (!is_array($j)) continue;
            $location = $j['location'] ?? 'Remote';
            $jobs[] = [
                'source' => 'devitjobs', 'source_url' => $j['url'] ?? $j['link'] ?? '',
                'title' => $j['title'] ?? $j['name'] ?? '', 'company' => $j['company'] ?? 'Various',
                'location' => $location, 'country' => detectCountry($location),
                'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'USD', 'description' => strip_tags(substr($j['description'] ?? '', 0, 500)),
                'tags' => json_encode(['devitjobs']), 'posted_at' => $j['date'] ?? date('Y-m-d H:i:s'),
            ];
        }
    } catch (Exception $e) {}
    return $jobs;
}

// Test
echo "jobicy: " . count(scrape_jobicy()) . "\n";
echo "devitjobs: " . count(scrape_devitjobs()) . "\n";
