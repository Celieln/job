<?php
require_once __DIR__ . '/../config.php';

function getDb() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
        initDatabase($db);
    }
    return $db;
}

function initDatabase($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source TEXT NOT NULL,
            source_url TEXT,
            title TEXT NOT NULL,
            company TEXT,
            location TEXT,
            country TEXT DEFAULT 'Global',
            job_type TEXT DEFAULT 'fulltime',
            salary_min REAL,
            salary_max REAL,
            salary_currency TEXT DEFAULT 'USD',
            description TEXT,
            tags TEXT,
            posted_at TEXT,
            scraped_at TEXT DEFAULT (datetime('now')),
            is_active INTEGER DEFAULT 1,
            link_status TEXT DEFAULT 'pending',
            last_validated TEXT
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS scan_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            scan_type TEXT,
            source TEXT,
            status TEXT,
            jobs_found INTEGER DEFAULT 0,
            jobs_added INTEGER DEFAULT 0,
            started_at TEXT DEFAULT (datetime('now')),
            completed_at TEXT
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS validation_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER,
            url TEXT,
            http_status INTEGER,
            status TEXT,
            checked_at TEXT DEFAULT (datetime('now'))
        )
    ");

    $db->exec('CREATE INDEX IF NOT EXISTS idx_jobs_source ON jobs(source)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_jobs_country ON jobs(country)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_jobs_active ON jobs(is_active)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_jobs_link_status ON jobs(link_status)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_jobs_scraped_at ON jobs(scraped_at)');
}
