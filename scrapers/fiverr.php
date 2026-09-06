<?php
/**
 * Fiverr - Freelance gigs
 */
function scrape_fiverr() {
    $jobs = [];
    
    $categories = [
        'programming-tech' => 'https://www.fiverr.com/categories/programming-tech',
        'digital-marketing' => 'https://www.fiverr.com/categories/digital-marketing',
        'graphics-design' => 'https://www.fiverr.com/categories/graphics-design',
    ];
    
    foreach ($categories as $catName => $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        // Extract gig links
        preg_match_all('/<a[^>]*href="(\/[a-z0-9_-]+\/[a-z0-9_-]+[^"]*)"[^>]*class="[^"]*gig[^"]*"[^>]*>(.*?)<\/a>/si', $html, $matches);
        
        if (empty($matches[1])) {
            // Try broader pattern
            preg_match_all('/<a[^>]*href="(\/[a-z0-9_-]+\/[a-z0-9_-]+_[^"]*)"[^>]*>(.*?)<\/a>/si', $html, $matches);
        }
        
        if (!empty($matches[1])) {
            for ($i = 0; $i < count($matches[1]) && $i < 20; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 5) continue;
                
                $user = explode('/', $matches[1][$i])[1] ?? 'freelancer';
                
                $jobs[] = [
                    'source' => 'fiverr',
                    'source_url' => 'https://www.fiverr.com' . $matches[1][$i],
                    'title' => 'Gig: ' . $title,
                    'company' => '@' . $user,
                    'location' => 'Remote - Global',
                    'country' => 'Global',
                    'job_type' => 'freelance',
                    'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'USD',
                    'description' => $title,
                    'tags' => json_encode(['fiverr', 'freelance', $catName]),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        usleep(800000);
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:fiverr.com programming', 'fiverr', 'Global');
    }
    
    return $jobs;
}
