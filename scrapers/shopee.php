<?php
/**
 * Shopee Jobs - Indonesian tech company
 */
function scrape_shopee() {
    $jobs = [];
    
    $json = fetchJson('https://careers.shopee.co.id/api/v1/jobs?limit=50');
    if ($json && isset($json['data'])) {
        foreach ($json['data'] as $j) {
            $jobs[] = [
                'source' => 'shopee', 'source_url' => $j['url'] ?? '',
                'title' => $j['title'] ?? '', 'company' => 'Shopee',
                'location' => 'Jakarta, Indonesia', 'country' => 'Indonesia',
                'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'IDR', 'description' => $j['description'] ?? $j['title'] ?? '',
                'tags' => json_encode(['shopee', 'indonesia', 'tech']),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:careers.shopee.co.id jobs', 'shopee', 'Indonesia');
    }
    
    return $jobs;
}
