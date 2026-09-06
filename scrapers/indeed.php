<?php
/**
 * Indeed - Job search RSS feeds
 */
function scrape_indeed() {
    $jobs = [];
    
    $feeds = [
        'https://id.indeed.com/rss?q=developer&l=Indonesia&sort=date',
        'https://id.indeed.com/rss?q=remote&l=Indonesia&sort=date',
        'https://cn.indeed.com/rss?q=developer&l=China&sort=date',
        'https://www.indeed.com/rss?q=remote+developer&sort=date',
    ];
    
    foreach ($feeds as $feedUrl) {
        $xml = fetchUrl($feedUrl);
        if (!$xml) continue;
        
        $rss = @simplexml_load_string($xml);
        if (!$rss || !isset($rss->channel->item)) continue;
        
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $desc = (string)$item->description;
            $pubDate = (string)$item->pubDate;
            
            $company = 'Various Companies';
            if (preg_match('/<span[^>]*class="company"[^>]*>([^<]+)/i', $desc, $cm)) {
                $company = trim($cm[1]);
            }
            
            $jobs[] = [
                'source' => 'indeed',
                'source_url' => $link,
                'title' => $title,
                'company' => $company,
                'location' => strpos($feedUrl, 'id.indeed') !== false ? 'Indonesia' : (strpos($feedUrl, 'cn.indeed') !== false ? 'China' : 'Global'),
                'country' => strpos($feedUrl, 'id.indeed') !== false ? 'Indonesia' : (strpos($feedUrl, 'cn.indeed') !== false ? 'China' : 'Global'),
                'job_type' => 'fulltime',
                'salary_min' => null, 'salary_max' => null,
                'salary_currency' => '',
                'description' => strip_tags($desc),
                'tags' => json_encode(['indeed']),
                'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
            ];
        }
        usleep(300000);
    }
    
    return $jobs;
}
