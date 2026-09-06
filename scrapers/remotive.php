<?php
function scrape_remotive() {
    $jobs = [];
    $json = @file_get_contents('https://remotive.com/api/remote-jobs?limit=250');
    if (!$json) return $jobs;
    $data = json_decode($json, true);
    if (!isset($data['jobs'])) return $jobs;

    foreach ($data['jobs'] as $j) {
        $location = $j['candidate_required_location'] ?? $j['location'] ?? 'Remote';
        $jobs[] = [
            'source' => 'remotive',
            'source_url' => $j['url'] ?? $j['apply_url'] ?? '',
            'title' => $j['title'] ?? '',
            'company' => $j['company_name'] ?? $j['company'] ?? '',
            'location' => $location,
            'country' => detectCountry($location),
            'job_type' => $j['job_type'] ?? 'fulltime',
            'salary_min' => null,
            'salary_max' => null,
            'salary_currency' => '',
            'description' => $j['description'] ?? '',
            'tags' => json_encode($j['tags'] ?? []),
            'posted_at' => $j['publication_date'] ?? date('Y-m-d H:i:s'),
        ];
    }
    return $jobs;
}
