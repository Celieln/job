<?php
/**
 * RemoteJobs.org
 */
function scrape_remotedotorg() {
    $jobs = [];
    
    $html = @file_get_contents('https://www.remotejobs.org/jobs', false, stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept: text/html\r\n",
            'timeout' => 15,
        ]
    ]));
    
    if (!$html) return $jobs;
    
    // Extract job listings
    preg_match_all('/<a[^>]*href="(\/jobs\/[^"]*)"[^>]*>(.*?)<\/a>/si', $html, $matches);
    
    if (!empty($matches[1])) {
        for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
            $link = $matches[1][$i];
            $title = strip_tags($matches[2][$i]);
            $title = trim(preg_replace('/\s+/', ' ', $title));
            
            if (strlen($title) < 3) continue;
            
            $jobs[] = [
                'source' => 'remotedotorg',
                'source_url' => 'https://www.remotejobs.org' . $link,
                'title' => $title,
                'company' => 'Remote Company',
                'location' => 'Remote',
                'country' => 'Global',
                'job_type' => 'fulltime',
                'salary_min' => null,
                'salary_max' => null,
                'salary_currency' => 'USD',
                'description' => $title,
                'tags' => json_encode(['remote', 'global']),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
    
    return $jobs;
}
