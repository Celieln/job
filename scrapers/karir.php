<?php
/**
 * Karir.com - Indonesia job board
 */
function scrape_karir() {
    $jobs = [];
    
    // Try RSS feed
    $xml = fetchUrl('https://www.karir.com/rss/jobs?q=developer');
    if ($xml) {
        $rss = @simplexml_load_string($xml);
        if ($rss && isset($rss->channel->item)) {
            foreach ($rss->channel->item as $item) {
                $jobs[] = [
                    'source' => 'karir', 'source_url' => (string)$item->link,
                    'title' => (string)$item->title, 'company' => 'Perusahaan Indonesia',
                    'location' => 'Indonesia', 'country' => 'Indonesia',
                    'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR',
                    'description' => strip_tags((string)$item->description),
                    'tags' => json_encode(['karir', 'indonesia']),
                    'posted_at' => isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string)$item->pubDate)) : date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    // Fallback: scrape HTML
    if (empty($jobs)) {
        $html = fetchUrl('https://www.karir.com/jobs?q=developer&sort=date');
        if ($html) {
            preg_match_all('/<a[^>]*href="(\/job\/[^"]*)"[^>]*>\s*([^<]+)/si', $html, $matches);
            for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 3) continue;
                $jobs[] = [
                    'source' => 'karir', 'source_url' => 'https://www.karir.com' . $matches[1][$i],
                    'title' => $title, 'company' => 'Perusahaan Indonesia',
                    'location' => 'Indonesia', 'country' => 'Indonesia',
                    'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR', 'description' => $title,
                    'tags' => json_encode(['karir', 'indonesia']),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:karir.com lowongan', 'karir', 'Indonesia');
    }
    
    return $jobs;
}
