<?php
/**
 * LokerID - Indonesian job board
 */
function scrape_lokerid() {
    $jobs = [];
    
    $urls = [
        'https://www.lokerid.com/lokerja/',
        'https://www.lokerid.com/loker-teknologi/',
    ];
    
    foreach ($urls as $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        $patterns = [
            '/<a[^>]*href="(https?:\/\/www\.lokerid\.com\/[^"]*\.html)"[^>]*>\s*([^<]+)/si',
            '/<h[234][^>]*>\s*<a[^>]*href="(https?:\/\/www\.lokerid\.com\/[^"]*)"[^>]*>([^<]+)/si',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                    $title = trim(strip_tags($matches[2][$i]));
                    if (strlen($title) < 5) continue;
                    
                    $company = 'Perusahaan Indonesia';
                    if (strpos($title, ' - ') !== false) {
                        $parts = explode(' - ', $title, 2);
                        $company = trim($parts[0]);
                        $title = trim($parts[1]);
                    }
                    
                    $jobs[] = [
                        'source' => 'lokerid', 'source_url' => $matches[1][$i],
                        'title' => $title, 'company' => $company,
                        'location' => 'Indonesia', 'country' => 'Indonesia',
                        'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                        'salary_currency' => 'IDR', 'description' => $title,
                        'tags' => json_encode(['lokerid', 'indonesia']),
                        'posted_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        usleep(500000);
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:lokerid.com lowongan', 'lokerid', 'Indonesia');
    }
    
    return $jobs;
}
