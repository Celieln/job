<?php
/**
 * 猎聘 (liepin.com) - China job platform
 */
function scrape_liepin() {
    $jobs = [];
    
    // Try API
    $json = fetchJson('https://www.liepin.com/api/com.liepin.searchfront4c.pc-search-job?data=%7B%22mainSearchPcCondition%22%3A%7B%22key%22%3A%22%E8%BF%9C%E7%A8%8B%22%2C%22currentPage%22%3A0%2C%22pageSize%22%3A30%7D%7D');
    
    if ($json && isset($json['data']['jobCardList'])) {
        foreach ($json['data']['jobCardList'] as $j) {
            $jobs[] = [
                'source' => 'liepin',
                'source_url' => 'https://www.liepin.com/job/' . ($j['dataId'] ?? ''),
                'title' => $j['job']['title'] ?? '',
                'company' => $j['comp']['compName'] ?? '中国公司',
                'location' => $j['job']['dq'] ?? 'China',
                'country' => 'China',
                'job_type' => 'fulltime',
                'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'CNY',
                'description' => $j['job']['title'] ?? '',
                'tags' => json_encode([]),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:liepin.com 远程开发', 'liepin', 'China');
    }
    
    return $jobs;
}
