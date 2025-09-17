<?php
return [
    'user_agent' => env('SCRAPING_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
    'selenium_host' => env('SELENIUM_HOST', 'http://localhost:4444/wd/hub'),
    'delay_between_requests' => env('SCRAPING_DELAY', 2),
    'timeout' => env('SCRAPING_TIMEOUT', 30),

    // Database settings
    'cleanup_old_jobs_days' => env('SCRAPING_CLEANUP_DAYS', 30),
    'duplicate_detection' => env('SCRAPING_DUPLICATE_DETECTION', true),

    // Logging
    'detailed_logging' => env('SCRAPING_DETAILED_LOGGING', false),
];
