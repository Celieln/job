<?php
/**
 * JobStreet Indonesia - Uses RSS feed
 */
function scrape_jobstreet() {
    $jobs = [];
    
    // Try RSS feed
    $rssUrls = [
        'https://www.jobstreet.co.id/rss/jobsearch?keyword=developer&location=Indonesia',
        'https://www.jobstreet.co.id/rss/jobsearch?keyword=remote&location=Indonesia',
    ];
    
    foreach ($rssUrls as $feedUrl) {
        $xml = fetchUrl($feedUrl);
        if (!$xml) continue;
        
        $rss = @simplexml_load_string($xml);
        if (!$rss || !isset($rss->channel->item)) continue;
        
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $desc = (string)$item->description;
            $pubDate = (string)$item->pubDate;
            
            // Extract company from description
            $company = 'Perusahaan Indonesia';
            if (preg_match('/class="[^"]*company[^"]*"[^>]*>([^<]+)/i', $desc, $cm)) {
                $company = trim($cm[1]);
            } elseif (preg_match('/by\s+([^-]+)/i', strip_tags($desc), $cm)) {
                $company = trim($cm[1]);
            }
            
            $jobs[] = [
                'source' => 'jobstreet', 'source_url' => $link,
                'title' => $title, 'company' => $company,
                'location' => 'Indonesia', 'country' => 'Indonesia',
                'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                'salary_currency' => 'IDR', 'description' => strip_tags($desc),
                'tags' => json_encode(['jobstreet', 'indonesia']),
                'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
            ];
        }
        usleep(300000);
    }
    
    // Fallback: scrape HTML with proper headers
    if (empty($jobs)) {
        $html = fetchUrl('https://www.jobstreet.co.id/id/find-jobs?keyword=developer&sortMode=ListedDate');
        if ($html) {
            preg_match_all('/<a[^>]*href="(\/id\/job\/[^"]*)"[^>]*>\s*<div[^>]*>\s*<h2[^>]*>([^<]+)/si', $html, $matches);
            for ($i = 0; $i < count($matches[1]) && $i < 30; $i++) {
                $title = trim(strip_tags($matches[2][$i]));
                if (strlen($title) < 3) continue;
                $jobs[] = [
                    'source' => 'jobstreet', 'source_url' => 'https://www.jobstreet.co.id' . $matches[1][$i],
                    'title' => $title, 'company' => 'Perusahaan Indonesia',
                    'location' => 'Indonesia', 'country' => 'Indonesia',
                    'job_type' => 'fulltime', 'salary_min' => null, 'salary_max' => null,
                    'salary_currency' => 'IDR', 'description' => $title,
                    'tags' => json_encode(['jobstreet', 'indonesia']),
                    'posted_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
    }
    
    if (empty($jobs)) {
        $jobs = scrapeViaGoogle('site:jobstreet.co.id lowongan developer', 'jobstreet', 'Indonesia');
    }
    
    return $jobs;
}
