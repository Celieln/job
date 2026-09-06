<?php
define('PORT', 3200);
define('DB_PATH', __DIR__ . '/database/jobs.db');
define('MAX_AGE_DAYS', 7);
define('VALIDATION_TIMEOUT', 15);
define('SCRAPE_INTERVAL', 300);
define('CLEAN_INTERVAL', 3600);
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('SESSION_SECRET', 'job-aggregator-secret-' . md5(__DIR__));

// Headless scrapers (Node.js/Puppeteer) - false di shared hosting
define('ENABLE_HEADLESS', true);

// Secret untuk trigger cron via URL
define('CRON_SECRET', 'jobcron2026xyz');
