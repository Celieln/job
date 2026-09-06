<?php
/**
 * Freelancer.com - RSS feed for projects
 */
function scrape_freelancer() {
    $jobs = [];
    
    $feeds = [
        'https://www.freelancer.com/rss/newprojects/?keyword=developer',
        'https://www.freelancer.com/rss/newprojects/?keyword=web+design',
        'https://www.freelancer.com/rss/newprojects/?keyword=php',
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
            
            if (strlen($title) < 5) continue;
            
            $jobs[] = [
                'source' => 'freelancer',
                'source_url' => $link,
                'title' => $title,
                'company' => 'Freelancer Client',
                'location' => 'Remote - Global',
                'country' => 'Global',
                'job_type' => 'freelance',
                'salary_min' => null,
                'salary_max' => null,
                'salary_currency' => 'USD',
                'description' => strip_tags($desc),
                'tags' => json_encode(['freelancer', 'freelance']),
                'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
            ];
        }
        usleep(500000);
    }
    
    return $jobs;
}
