<?php
/**
 * 前程无忧 (51job.com) - China job platform
 */
function scrape_51job() {
    $jobs = [];
    
    // Try API
    $json = fetchJson('https://search.51job.com/list/000000,000000,0000,00,9,99,%25E8%25BF%259C%25E7%25A8%258B,2,1.html?lang=c&postchannel=0000&workyear=99&cotype=3&degreefrom=99&jobterm=99&companysize=99&ord_field=0&dibession=99&line=&welfare=');
    
    if ($json && isset($json['resultbody']['job']['items'])) {
        foreach ($json['resultbody']['job']['items'] as $j) {
            $jobs[] = [
                'source' => '51job',
                'source_url' => $j['jobHref'] ?? '',
                'title' => $j['jobName'] ?? '',
                'company' => $j['companyName'] ?? '中国公司',
                'location' => $j['jobArea'] ?? 'China',
                'country' => 'China',
                'job_type' => 'fulltime',
                'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'CNY',
                'description' => $j['jobName'] ?? '',
                'tags' => json_encode([]),
                'posted_at' => $j['issueDate'] ?? date('Y-m-d H:i:s'),
            ];
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:51job.com 远程开发', '51job', 'China');
    }
    
    return $jobs;
}
