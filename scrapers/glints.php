<?php
/**
 * Glints - Indonesia/SEA job platform
 * Uses proper API endpoint
 */
function scrape_glints() {
    $jobs = [];
    
    // Glints has an API that returns JSON
    $apiUrl = 'https://glints.com/api/v2/opportunities?country=ID&type=FULLTIME&sort=latest&limit=50';
    $json = fetchUrl($apiUrl);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['data'])) {
            foreach ($data['data'] as $j) {
                $jobs[] = [
                    'source' => 'glints',
                    'source_url' => 'https://glints.com/id/opportunities/' . ($j['slug'] ?? ''),
                    'title' => $j['title'] ?? '',
                    'company' => $j['company']['name'] ?? 'Various',
                    'location' => $j['city'] ?? 'Jakarta',
                    'country' => 'Indonesia',
                    'job_type' => strtolower($j['employmentType'] ?? 'fulltime'),
                    'salary_min' => $j['salary']['min'] ?? null,
                    'salary_max' => $j['salary']['max'] ?? null,
                    'salary_currency' => $j['salary']['currency'] ?? 'IDR',
                    'description' => strip_tags($j['description'] ?? ''),
                    'tags' => json_encode($j['skillNames'] ?? []),
                    'posted_at' => $j['createdAt'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    // Fallback: scrape HTML
    if (empty($jobs)) {
        $html = fetchUrl('https://glints.com/id/opportunities/jobs/explore?country=ID&type=FULLTIME&sort=latest');
        if ($html) {
            preg_match_all('/<a[^>]*href="(\/id\/opportunities\/[^"]*)"[^>]*class="[^"]*"[^>]*>(.*?)<\/a>/si', $html, $matches);
            for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 3) continue;
                $jobs[] = [
                    'source' => 'glints', 'source_url' => 'https://glints.com' . $matches[1][$i],
                    'title' => $title, 'company' => 'Perusahaan Indonesia',
                    'location' => 'Jakarta, Indonesia', 'country' => 'Indonesia',
                    'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR', 'description' => $title,
                    'tags' => json_encode(['glints', 'indonesia']),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    // Final fallback: Google search
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:glints.com jobs indonesia', 'glints', 'Indonesia');
    }
    
    return $jobs;
}
