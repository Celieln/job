<?php
/**
 * Arbeitnow - Remote jobs RSS
 */
function scrape_arbeitnow() {
    $jobs = [];
    
    $xml = @file_get_contents('https://www.arbeitnow.com/api/job-board-api');
    if (!$xml) return $jobs;
    
    $data = json_decode($xml, true);
    if (!isset($data['data'])) return $jobs;
    
    foreach ($data['data'] as $j) {
        $tags = $j['tags'] ?? [];
        
        $jobs[] = [
            'source' => 'arbeitnow',
            'source_url' => $j['url'] ?? $j['apply_url'] ?? '',
            'title' => $j['title'] ?? '',
            'company' => $j['company_name'] ?? $j['company'] ?? '',
            'location' => $j['location'] ?? 'Remote',
            'country' => detectCountry($j['location'] ?? 'Remote'),
            'job_type' => 'fulltime',
            'salary_min' => null,
            'salary_max' => null,
            'salary_currency' => '',
            'description' => $j['description'] ?? '',
            'tags' => json_encode($tags),
            'posted_at' => $j['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }
    
    return $jobs;
}
