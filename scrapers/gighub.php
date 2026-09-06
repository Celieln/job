<?php
/**
 * Gighub.id - Indonesian freelance platform
 * Uses proper cURL with browser headers
 */
function scrape_gighub() {
    $jobs = [];
    $urls = [
        'https://gighub.id/projects',
        'https://gighub.id/projects?category=web-development',
        'https://gighub.id/projects?category=design',
    ];
    
    foreach ($urls as $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        // Look for project links in various patterns
        $patterns = [
            '/<a[^>]*href="([^"]*\/projects\/[^"]*)"[^>]*>\s*<[^>]*>([^<]+)/si',
            '/<h[23][^>]*>\s*<a[^>]*href="([^"]*)"[^>]*>([^<]+)/si',
            '/<div[^>]*class="[^"]*project[^"]*"[^>]*>.*?<a[^>]*href="([^"]*)"[^>]*>([^<]+)/si',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                    $link = $matches[1][$i];
                    $title = strip_tags($matches[2][$i]);
                    $title = trim(preg_replace('/\s+/', ' ', $title));
                    if (strlen($title) < 5) continue;
                    if (!str_starts_with($link, 'http')) $link = 'https://gighub.id' . $link;
                    
                    $jobs[] = [
                        'source' => 'gighub', 'source_url' => $link,
                        'title' => $title, 'company' => 'UMKM Indonesia',
                        'location' => 'Remote - Indonesia', 'country' => 'Indonesia',
                        'job_type' => 'freelance', 'salary_min' => null, 'salary_max' => null,
                        'salary_currency' => 'IDR', 'description' => $title,
                        'tags' => json_encode(['gighub', 'freelance', 'indonesia']),
                        'posted_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        usleep(500000);
    }
    
    // Fallback: use Google Custom Search for gighub.id jobs
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:gighub.id project', 'gighub', 'Indonesia');
    }
    
    return $jobs;
}
