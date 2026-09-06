<?php
function scrape_himalayas() {
    $jobs = [];
    $limit = 50;
    
    for ($offset = 0; $offset < 200; $offset += $limit) {
        $json = @file_get_contents("https://himalayas.app/jobs/api?limit={$limit}&offset={$offset}");
        if (!$json) break;
        $data = json_decode($json, true);
        if (empty($data['jobs'])) break;

        foreach ($data['jobs'] as $j) {
            $location = is_array($j['locationRestrictions'] ?? null) ? implode(', ', $j['locationRestrictions']) : 'Remote';
            $jobs[] = [
                'source' => 'himalayas',
                'source_url' => $j['applicationLink'] ?? $j['guid'] ?? '',
                'title' => $j['title'] ?? '',
                'company' => $j['companyName'] ?? '',
                'location' => $location,
                'country' => detectCountry($location),
                'job_type' => $j['employmentType'] ?? 'fulltime',
                'salary_min' => isset($j['minSalary']) ? (float)$j['minSalary'] : null,
                'salary_max' => isset($j['maxSalary']) ? (float)$j['maxSalary'] : null,
                'salary_currency' => $j['currency'] ?? 'USD',
                'description' => strip_tags(substr($j['description'] ?? $j['excerpt'] ?? '', 0, 500)),
                'tags' => json_encode($j['categories'] ?? []),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
        usleep(500000);
    }
    return $jobs;
}
