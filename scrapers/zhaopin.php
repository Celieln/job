<?php
/**
 * 智联招聘 (zhaopin.com) - China job platform
 */
function scrape_zhaopin() {
    $jobs = [];
    
    // Try direct API
    $json = fetchJson('https://fe-api.zhaopin.com/c/i/sou?kw=远程&cityId=530&pageSize=30&start=0');
    
    if ($json && isset($json['data']['results'])) {
        foreach ($json['data']['results'] as $j) {
            $jobs[] = [
                'source' => 'zhaopin',
                'source_url' => $j['positionURL'] ?? '',
                'title' => $j['jobName'] ?? '',
                'company' => $j['company']['name'] ?? '中国公司',
                'location' => $j['city']['display'] ?? 'China',
                'country' => 'China',
                'job_type' => 'fulltime',
                'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'CNY',
                'description' => $j['jobName'] ?? '',
                'tags' => json_encode([]),
                'posted_at' => $j['updateDate'] ?? date('Y-m-d H:i:s'),
            ];
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:zhaopin.com 远程开发', 'zhaopin', 'China');
    }
    
    return $jobs;
}
