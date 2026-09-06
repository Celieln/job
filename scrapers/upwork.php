<?php
/**
 * Upwork - RSS feed for freelance jobs
 */
function scrape_upwork() {
    $jobs = [];
    
    // Upwork RSS feeds
    $feeds = [
        'https://www.upwork.com/ab/feed/jobs/rss?q=developer&sort=recency&job_type=fixed',
        'https://www.upwork.com/ab/feed/jobs/rss?q=web+design&sort=recency',
        'https://www.upwork.com/ab/feed/jobs/rss?q=php+python&sort=recency',
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
            
            // Skip if title looks like spam
            if (strlen($title) < 5) continue;
            
            $jobs[] = [
                'source' => 'upwork',
                'source_url' => $link,
                'title' => $title,
                'company' => 'Upwork Client',
                'location' => 'Remote - Global',
                'country' => 'Global',
                'job_type' => 'freelance',
                'salary_min' => null,
                'salary_max' => null,
                'salary_currency' => 'USD',
                'description' => strip_tags($desc),
                'tags' => json_encode(['upwork', 'freelance']),
                'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
            ];
        }
        usleep(500000);
    }
    
    return $jobs;
}
