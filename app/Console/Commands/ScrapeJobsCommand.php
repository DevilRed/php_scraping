<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobScrapingService;
class ScrapeJobsCommand extends Command
{
    protected $signature = 'jobs:scrape
                          {--company= : Specific company to scrape}
                          {--filters= : JSON string of filters to apply}
                          {--no-save : Do not save to database}
                          {--stats : Show scraping statistics}
                          {--cleanup : Clean up old job listings}';

    protected $description = 'Scrape job listings from various company websites';

    public function handle(JobScrapingService $scrapingService): int
    {
        // Handle stats display
        if ($this->option('stats')) {
            return $this->showStats($scrapingService);
        }

        // Handle cleanup
        if ($this->option('cleanup')) {
            return $this->cleanupOldJobs($scrapingService);
        }

        $company = $this->option('company');
        $filters = $this->option('filters') ? json_decode($this->option('filters'), true) : [];
        $saveToDatabase = !$this->option('no-save');

        try {
            if ($company) {
                $this->info("Scraping jobs for {$company}...");
                $jobs = $scrapingService->scrapeCompany($company, $filters, $saveToDatabase);
                $this->info("Found {$jobs->count()} jobs for {$company}");
            } else {
                $this->info('Scraping all companies...');
                $jobs = $scrapingService->scrapeAll($saveToDatabase);
                $this->info("Total jobs scraped: {$jobs->count()}");
            }

            if ($jobs->isEmpty()) {
                $this->warn('No jobs found!');
                return Command::SUCCESS;
            }

            // Display results
            $this->table(
                ['Company', 'Title', 'Location', 'URL'],
                $jobs->map(fn($job) => [
                    $job->company,
                    \Str::limit($job->title, 30),
                    $job->location,
                    \Str::limit($job->url, 50)
                ])->toArray()
            );

            // Show save summary if saved to database
            if ($saveToDatabase) {
                $this->info("\nJobs have been saved to the database.");
                $this->call('jobs:scrape', ['--stats' => true]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Scraping failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStats(JobScrapingService $scrapingService): int
    {
        $this->info('Scraping Statistics (Last 7 days)');
        $this->line('');

        foreach ($scrapingService->getAvailableCompanies() as $company) {
            $stats = $scrapingService->getScrapingStats($company, 7);

            $this->line("--- {$company} ---");
            $this->line("Total runs: {$stats['total_runs']}");
            $this->line("Successful: {$stats['successful_runs']}");
            $this->line("Failed: {$stats['failed_runs']}");
            $this->line("Jobs found: {$stats['total_jobs_found']}");
            $this->line("Jobs saved: {$stats['total_jobs_saved']}");

            if ($stats['average_duration']) {
                $this->line("Avg duration: " . round($stats['average_duration'], 1) . " seconds");
            }

            if ($stats['last_successful_run']) {
                $this->line("Last success: {$stats['last_successful_run']->diffForHumans()}");
            }

            $this->line('');
        }

        return Command::SUCCESS;
    }

    private function cleanupOldJobs(JobScrapingService $scrapingService): int
    {
        $days = $this->ask('How many days of job listings to keep?', 30);

        if ($this->confirm("This will delete job listings older than {$days} days. Continue?")) {
            $deleted = $scrapingService->cleanupOldJobs((int)$days);
            $this->info("Deleted {$deleted} old job listings.");
        } else {
            $this->info('Cleanup cancelled.');
        }

        return Command::SUCCESS;
    }
}
