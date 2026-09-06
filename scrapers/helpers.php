<?php
/**
 * Shared helper functions for all scrapers
 */

/**
 * Fetch URL with proper browser headers (cURL)
 */
function fetchUrl($url, $timeout = 15) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,id;q=0.8',
            'Cache-Control: no-cache',
        ],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err || $code >= 400 || !$html) return null;
    return $html;
}

/**
 * Fetch JSON API with proper headers
 */
function fetchJson($url, $timeout = 15) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9,id;q=0.8',
        ],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code >= 400 || !$json) return null;
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

/**
 * Scrape jobs via Google Custom Search (fallback)
 * Uses Google's public job search results
 */
function scrapeViaGoogle($query, $source, $country) {
    $jobs = [];
    
    // Use Google's public RSS/Atom feed for job search
    $searchUrl = 'https://www.google.com/search?q=' . urlencode($query) . '&num=20&hl=en';
    $html = fetchUrl($searchUrl);
    if (!$html) return $jobs;
    
    // Extract search results
    preg_match_all('/<a[^>]*href="\/url\?q=([^&"]+)/si', $html, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $i => $url) {
            $url = urldecode($url);
            if (!str_starts_with($url, 'http')) continue;
            if (strpos($url, 'google.com') !== false) continue;
            if (strpos($url, 'youtube.com') !== false) continue;
            
            // Extract title from surrounding HTML
            $title = ucwords(str_replace(['-', '_', '/'], ' ', basename(parse_url($url, PHP_URL_PATH))));
            $title = preg_replace('/\.[^.]+$/', '', $title);
            if (strlen($title) < 3) $title = ucwords($query) . ' #' . ($i + 1);
            
            $jobs[] = [
                'source' => $source,
                'source_url' => $url,
                'title' => $title,
                'company' => parse_url($url, PHP_URL_HOST),
                'location' => $country,
                'country' => $country,
                'job_type' => 'fulltime',
                'salary_min' => null,
                'salary_max' => null,
                'salary_currency' => '',
                'description' => 'Found via search: ' . $query,
                'tags' => json_encode([$source, strtolower($country)]),
                'posted_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
    
    return $jobs;
}

function detectCountry($location) {
    $loc = strtolower($location);
    if (preg_match('/indonesia|jakarta|bandung|surabaya|medan|yogyakarta|bali|makassar|semarang|tangerang|bekasi|depok/i', $loc)) return 'Indonesia';
    if (preg_match('/china|beijing|shanghai|shenzhen|guangzhou|hangzhou|chengdu|wuhan|nanjing|suzhou|xiamen|中国|北京|上海|深圳|广州|杭州/i', $loc)) return 'China';
    if (preg_match('/usa|united states|new york|san francisco|los angeles|seattle|austin|boston|chicago|denver|miami|portland|sf\b|nyc\b/i', $loc)) return 'USA';
    if (preg_match('/uk|united kingdom|london|manchester|birmingham|edinburgh|glasgow|liverpool|bristol|cambridge|oxford/i', $loc)) return 'UK';
    if (preg_match('/europe|germany|berlin|munich|amsterdam|paris|france|spain|barcelona|madrid|italy|rome|sweden|stockholm|copenhagen|dublin|ireland|poland|warsaw/i', $loc)) return 'Europe';
    if (preg_match('/japan|tokyo|osaka|korea|seoul|singapore|hong kong|taiwan|taipei|bangkok|thailand|vietnam|manila|philippines|malaysia|mumbai|delhi|bangalore|india/i', $loc)) return 'Asia';
    if (preg_match('/canada|toronto|vancouver|montreal|calgary/i', $loc)) return 'Canada';
    if (preg_match('/australia|sydney|melbourne|brisbane|perth|auckland|new zealand/i', $loc)) return 'Australia';
    if (preg_match('/brazil|sao paulo|mexico|argentina|buenos aires|colombia|chile|peru/i', $loc)) return 'Latin America';
    if (preg_match('/remote|worldwide|anywhere|global|distributed|work from home|wfh/i', $loc)) return 'Global';
    return 'Global';
}
