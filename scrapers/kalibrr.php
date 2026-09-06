<?php
/**
 * Kalibrr - Indonesia tech job platform
 * Uses RSS or JSON API
 */
function scrape_kalibrr() {
    $jobs = [];
    
    // Try Kalibrr API
    $apiUrl = 'https://www.kalibrr.com/api/v1/jobs?country=ID&limit=50';
    $json = fetchUrl($apiUrl);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['jobs'])) {
            foreach ($data['jobs'] as $j) {
                $jobs[] = [
                    'source' => 'kalibrr',
                    'source_url' => 'https://www.kalibrr.com/id-ID/rec/' . ($j['id'] ?? ''),
                    'title' => $j['title'] ?? '',
                    'company' => $j['company']['name'] ?? 'Tech Company',
                    'location' => $j['location'] ?? 'Indonesia',
                    'country' => 'Indonesia',
                    'job_type' => 'fulltime',
                    'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR',
                    'description' => strip_tags($j['description'] ?? ''),
                    'tags' => json_encode($j['skills'] ?? []),
                    'posted_at' => $j['posted_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    // Fallback: scrape HTML
    if (empty($jobs)) {
        $html = fetchUrl('https://www.kalibrr.com/id-ID/jobs');
        if ($html) {
            preg_match_all('/<a[^>]*href="(\/id-ID\/rec\/[^"]*)"[^>]*>([^<]+)/si', $html, $matches);
            for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 3) continue;
                $jobs[] = [
                    'source' => 'kalibrr', 'source_url' => 'https://www.kalibrr.com' . $matches[1][$i],
                    'title' => $title, 'company' => 'Tech Company Indonesia',
                    'location' => 'Indonesia', 'country' => 'Indonesia',
                    'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR', 'description' => $title,
                    'tags' => json_encode(['kalibrr', 'indonesia', 'tech']),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:kalibrr.com jobs', 'kalibrr', 'Indonesia');
    }
    
    return $jobs;
}
