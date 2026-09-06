<?php
function scrape_remoteok() {
    $jobs = [];
    $json = file_get_contents('https://remoteok.com/api');
    if (!$json) return $jobs;
    $data = json_decode($json, true);
    if (!is_array($data)) return $jobs;

    for ($i = 1; $i < count($data); $i++) {
        $j = $data[$i];
        $location = $j['location'] ?? 'Remote';
        $jobs[] = [
            'source' => 'remoteok',
            'source_url' => $j['apply_url'] ?? "https://remoteok.com/remote-jobs/{$j['id']}",
            'title' => $j['position'] ?? $j['title'] ?? '',
            'company' => $j['company'] ?? '',
            'location' => $location,
            'country' => detectCountry($location),
            'job_type' => ($j['full_time'] ?? false) ? 'fulltime' : 'contract',
            'salary_min' => $j['salary_min'] ?? null,
            'salary_max' => $j['salary_max'] ?? null,
            'salary_currency' => $j['salary_currency'] ?? 'USD',
            'description' => $j['description'] ?? '',
            'tags' => json_encode($j['tags'] ?? []),
            'posted_at' => $j['date'] ?? date('Y-m-d H:i:s'),
        ];
    }
    return $jobs;
}
