<?php
/**
 * Projects.co.id - Indonesian freelance platform
 */
function scrape_projects_co_id() {
    $jobs = [];
    $urls = [
        'https://projects.co.id/projects',
        'https://projects.co.id/projects?category=web-development',
        'https://projects.co.id/projects?category=mobile-development',
    ];
    
    foreach ($urls as $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        $patterns = [
            '/<a[^>]*href="([^"]*\/projects\/[^"]*)"[^>]*>\s*([^<]+)/si',
            '/<h[234][^>]*>\s*<a[^>]*href="([^"]*)"[^>]*>([^<]+)/si',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                    $link = $matches[1][$i];
                    $title = trim(strip_tags($matches[2][$i]));
                    if (strlen($title) < 5) continue;
                    if (!str_starts_with($link, 'http')) $link = 'https://projects.co.id' . $link;
                    
                    $jobs[] = [
                        'source' => 'projects_co_id', 'source_url' => $link,
                        'title' => $title, 'company' => 'Klien Indonesia',
                        'location' => 'Remote - Indonesia', 'country' => 'Indonesia',
                        'job_type' => 'freelance', 'salary_min' => null, 'salary_max' => null,
                        'salary_currency' => 'IDR', 'description' => $title,
                        'tags' => json_encode(['projects_co_id', 'freelance', 'indonesia']),
                        'posted_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        usleep(500000);
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:projects.co.id project', 'projects_co_id', 'Indonesia');
    }
    
    return $jobs;
}
