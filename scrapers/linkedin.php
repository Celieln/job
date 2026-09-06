<?php
/**
 * LinkedIn - Professional job search
 * Uses RSS feed + Google search
 */
function scrape_linkedin() {
    $jobs = [];
    
    // LinkedIn public job search RSS
    $feeds = [
        'https://www.linkedin.com/jobs/search?keywords=remote+developer&location=Indonesia&f_TPR=r604800&sortBy=DD',
        'https://www.linkedin.com/jobs/search?keywords=software+engineer&location=Indonesia&f_TPR=r604800',
        'https://www.linkedin.com/jobs/search?keywords=remote+developer&location=China&f_TPR=r604800',
        'https://www.linkedin.com/jobs/search?keywords=remote+developer&location=Global&f_TPR=r604800',
    ];
    
    foreach ($feeds as $url) {
        $html = fetchUrl($url);
        if (!$html) continue;
        
        // LinkedIn uses JS rendering, so extract what we can
        preg_match_all('/<a[^>]*href="(\/jobs\/view\/[^"]*)"[^>]*>(.*?)<\/a>/si', $html, $matches);
        
        if (!empty($matches[1])) {
            for ($i = 0; $i < count($matches[1]) && $i < 20; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 3) continue;
                
                $jobs[] = [
                    'source' => 'linkedin',
                    'source_url' => 'https://www.linkedin.com' . $matches[1][$i],
                    'title' => $title,
                    'company' => 'Various Companies',
                    'location' => 'Remote',
                    'country' => detectCountry($url),
                    'job_type' => 'fulltime',
                    'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => '',
                    'description' => $title,
                    'tags' => json_encode(['linkedin', 'professional']),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        usleep(500000);
    }
    
    // Fallback: Google search
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:linkedin.com/jobs/view remote developer', 'linkedin', 'Global');
    }
    
    return $jobs;
}
