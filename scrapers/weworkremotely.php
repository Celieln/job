<?php
function scrape_weworkremotely() {
    $jobs = [];
    $xml = @file_get_contents('https://weworkremotely.com/remote-jobs.rss');
    if (!$xml) return $jobs;

    $rss = @simplexml_load_string($xml);
    if (!$rss || !isset($rss->channel->item)) return $jobs;

    foreach ($rss->channel->item as $item) {
        $title = (string)$item->title;
        $link = (string)$item->link;
        $desc = (string)$item->description;
        $pubDate = (string)$item->pubDate;

        $parts = explode(':', $title, 2);
        $company = trim($parts[0] ?? '');
        $jobTitle = trim($parts[1] ?? $title);

        $tags = [];
        if (isset($item->category)) {
            foreach ($item->category as $cat) {
                $tags[] = (string)$cat;
            }
        }

        $jobs[] = [
            'source' => 'weworkremotely',
            'source_url' => $link,
            'title' => $jobTitle,
            'company' => $company,
            'location' => 'Remote',
            'country' => 'Global',
            'job_type' => 'fulltime',
            'salary_min' => null,
            'salary_max' => null,
            'salary_currency' => '',
            'description' => strip_tags($desc),
            'tags' => json_encode($tags),
            'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s'),
        ];
    }
    return $jobs;
}
