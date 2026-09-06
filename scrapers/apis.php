<?php
/**
 * Jooble API - Job aggregator (free tier available)
 * https://jooble.org/api/about
 */
function scrape_jooble() {
    $jobs = [];
    
    $apiKeys = ['']; // Add your free API key from jooble.org/api
    
    foreach ($apiKeys as $apiKey) {
        if (empty($apiKey)) continue;
        
        $data = fetchJson("https://jooble.org/api/{$apiKey}", 15);
        if ($data && isset($data['jobs'])) {
            foreach ($data['jobs'] as $j) {
                $jobs[] = [
                    'source' => 'jooble',
                    'source_url' => $j['link'] ?? '',
                    'title' => $j['title'] ?? '',
                    'company' => $j['company'] ?? 'Various',
                    'location' => $j['location'] ?? 'Remote',
                    'country' => detectCountry($j['location'] ?? ''),
                    'job_type' => 'fulltime',
                    'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => '',
                    'description' => $j['snippet'] ?? '',
                    'tags' => json_encode(['jooble', 'aggregator']),
                    'posted_at' => $j['pubDate'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    return $jobs;
}

/**
 * Adzuna API - Job data (free tier: 250 calls/month)
 * https://developer.adzuna.com/overview
 */
function scrape_adzuna() {
    $jobs = [];
    
    // Free API credentials (sign up at developer.adzuna.com)
    $appId = ''; // Add your app_id
    $appKey = ''; // Add your app_key
    
    if (empty($appId) || empty($appKey)) return $jobs;
    
    $countries = ['id', 'us', 'gb', 'de', 'in'];
    
    foreach ($countries as $country) {
        $url = "https://api.adzuna.com/v1/api/jobs/{$country}/search/1?app_id={$appId}&app_key={$appKey}&results_per_page=50&what=developer&content-type=application/json";
        $data = fetchJson($url);
        
        if ($data && isset($data['results'])) {
            foreach ($data['results'] as $j) {
                $jobs[] = [
                    'source' => 'adzuna',
                    'source_url' => $j['redirect_url'] ?? '',
                    'title' => $j['title'] ?? '',
                    'company' => $j['company']['display_name'] ?? 'Various',
                    'location' => $j['location']['display_name'] ?? '',
                    'country' => detectCountry($j['location']['display_name'] ?? ''),
                    'job_type' => 'fulltime',
                    'salary_min' => $j['salary_min'] ?? null,
                    'salary_max' => $j['salary_max'] ?? null,
                    'salary_currency' => $j['salary_is_predicted'] ?? '',
                    'description' => $j['description'] ?? '',
                    'tags' => json_encode($j['tags'] ?? []),
                    'posted_at' => $j['created'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
        usleep(500000);
    }
    
    return $jobs;
}

/**
 * CareerJet API - Job search (free tier available)
 * https://www.careerjet.com/partners/api/
 */
function scrape_careerjet() {
    $jobs = [];
    
    // Free API access key (sign up at careerjet.com)
    $accessKey = ''; // Add your access_key
    
    if (empty($accessKey)) return $jobs;
    
    $countries = ['id', 'us', 'gb'];
    
    foreach ($countries as $country) {
        $url = "https://public.api.careerjet.net/search?locale_code=en_{$country}&keywords=developer&limit=50&access_key={$accessKey}";
        $data = fetchJson($url);
        
        if ($data && isset($data['jobs'])) {
            foreach ($data['jobs'] as $j) {
                $jobs[] = [
                    'source' => 'careerjet',
                    'source_url' => $j['url'] ?? '',
                    'title' => $j['title'] ?? '',
                    'company' => $j['company'] ?? 'Various',
                    'location' => $j['locations'] ?? '',
                    'country' => detectCountry($j['locations'] ?? ''),
                    'job_type' => 'fulltime',
                    'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => '',
                    'description' => $j['description'] ?? '',
                    'tags' => json_encode([]),
                    'posted_at' => $j['date'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
        usleep(500000);
    }
    
    return $jobs;
}

/**
 * Findwork.dev API - Remote tech jobs
 */
function scrape_findwork() {
    $jobs = [];
    
    $data = fetchJson('https://findwork.dev/api/jobs/?order_by=-date_posted');
    if (!$data || !isset($data['results'])) return $jobs;
    
    foreach ($data['results'] as $j) {
        $jobs[] = [
            'source' => 'findwork',
            'source_url' => $j['url'] ?? $j['text'] ?? '',
            'title' => $j['role'] ?? '',
            'company' => $j['company_name'] ?? '',
            'location' => $j['location'] ?? 'Remote',
            'country' => detectCountry($j['location'] ?? ''),
            'job_type' => 'fulltime',
            'salary_min' => null, 'salary_max' => null,
            'salary_currency' => '',
            'description' => $j['text'] ?? '',
            'tags' => json_encode($j['employment_type'] ? [$j['employment_type']] : []),
            'posted_at' => $j['date_posted'] ?? date('Y-m-d H:i:s'),
        ];
    }
    
    return $jobs;
}

/**
 * ThinkRemote API - Remote jobs
 */
function scrape_thinkremote() {
    $jobs = [];
    
    $data = fetchJson('https://thinkremote-api.herokuapp.com/api/v1/jobs');
    if (!$data || !isset($data['jobs'])) return $jobs;
    
    foreach ($data['jobs'] as $j) {
        $jobs[] = [
            'source' => 'thinkremote',
            'source_url' => $j['url'] ?? '',
            'title' => $j['title'] ?? '',
            'company' => $j['company'] ?? '',
            'location' => $j['location'] ?? 'Remote',
            'country' => detectCountry($j['location'] ?? ''),
            'job_type' => 'fulltime',
            'salary_min' => null, 'salary_max' => null,
            'salary_currency' => '',
            'description' => $j['description'] ?? '',
            'tags' => json_encode($j['tags'] ?? []),
            'posted_at' => $j['date'] ?? date('Y-m-d H:i:s'),
        ];
    }
    
    return $jobs;
}
