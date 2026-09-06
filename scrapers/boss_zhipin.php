<?php
/**
 * BOSS直聘 (zhipin.com) - China's #1 job platform
 * Uses web search as primary method (site blocks direct scraping)
 */
function scrape_boss_zhipin() {
    $jobs = [];
    
    // Try direct API
    $json = fetchJson('https://www.zhipin.com/wapi/zpgeek/search/joblist.json?query=远程&city=100010000&experience=&payType=&partTime=&degree=&industry=&scale=&stage=&position=&jobType=&salary=&multiBusinessDistrict=&multiSubway=&page=1&pageSize=30');
    
    if ($json && isset($json['zpData']['jobList'])) {
        foreach ($json['zpData']['jobList'] as $j) {
            $jobs[] = [
                'source' => 'boss_zhipin',
                'source_url' => 'https://www.zhipin.com/job_detail/' . ($j['encryptJobId'] ?? '') . '.html',
                'title' => $j['jobName'] ?? '',
                'company' => $j['brandName'] ?? '中国公司',
                'location' => $j['cityName'] ?? 'China',
                'country' => 'China',
                'job_type' => 'fulltime',
                'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'CNY',
                'description' => ($j['jobName'] ?? '') . ' - ' . ($j['skills'] ?? ''),
                'tags' => json_encode($j['skills'] ?? []),
                'posted_at' => date('Y-m-d H:i:s', ($j['updateTime'] ?? time())),
            ];
        }
    }
    
    // Fallback: Google search
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:zhipin.com 远程开发', 'boss_zhipin', 'China');
    }
    
    return $jobs;
}
