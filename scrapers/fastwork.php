<?php
/**
 * Fastwork.id - Indonesian freelance platform
 */
function scrape_fastwork() {
    $jobs = [];
    $urls = [
        'https://fastwork.id/category/programming-tech',
        'https://fastwork.id/category/design-creative',
    ];
    
    foreach ($urls as $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        $patterns = [
            '/<a[^>]*href="(\/user\/[^"]*\/[^"]*)"[^>]*>\s*([^<]+)/si',
            '/<a[^>]*href="(\/service\/[^"]*)"[^>]*>\s*([^<]+)/si',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                    $link = $matches[1][$i];
                    $title = trim(strip_tags($matches[2][$i]));
                    if (strlen($title) < 5) continue;
                    
                    $jobs[] = [
                        'source' => 'fastwork', 'source_url' => 'https://fastwork.id' . $link,
                        'title' => 'Gig: ' . $title, 'company' => 'Freelancer Indonesia',
                        'location' => 'Remote - Indonesia', 'country' => 'Indonesia',
                        'job_type' => 'freelance', 'salary_min' => null, 'salary_max' => null,
                        'salary_currency' => 'IDR', 'description' => $title,
                        'tags' => json_encode(['fastwork', 'freelance', 'indonesia']),
                        'posted_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        usleep(500000);
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:fastwork.id service', 'fastwork', 'Indonesia');
    }
    
    return $jobs;
}
