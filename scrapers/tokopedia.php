<?php
/**
 * Tokopedia Jobs - Indonesian tech company
 */
function scrape_tokopedia() {
    $jobs = [];
    
    // Try career API
    $json = fetchJson('https://career.tokopedia.com/api/v1/jobs?limit=50');
    if ($json && isset($json['data'])) {
        foreach ($json['data'] as $j) {
            $jobs[] = [
                'source' => 'tokopedia', 'source_url' => $j['url'] ?? '',
                'title' => $j['title'] ?? '', 'company' => 'Tokopedia',
                'location' => 'Jakarta, Indonesia', 'country' => 'Indonesia',
                'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'IDR', 'description' => $j['description'] ?? $j['title'] ?? '',
                'tags' => json_encode(['tokopedia', 'indonesia', 'tech']),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:career.tokopedia.com jobs', 'tokopedia', 'Indonesia');
    }
    
    return $jobs;
}
