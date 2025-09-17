<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\ScrapingLog;
use App\Services\Scraping\Contracts\ScrapingStrategyInterface;
use App\Services\Scraping\Strategies\AssureSoftStrategy;
use App\Services\Scraping\Strategies\JalaSoftStrategy;
use App\Services\Scraping\Strategies\UnosquareStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class JobScrapingService
{
    private array $strategies = [];

    public function __construct()
    {
        // Register strategies - could be moved to config or service provider
        $this->registerStrategy(app(AssureSoftStrategy::class));
        $this->registerStrategy(app(UnosquareStrategy::class));
        $this->registerStrategy(app(JalaSoftStrategy::class));
    }

    public function registerStrategy(ScrapingStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getCompanyName()] = $strategy;
    }

    public function scrapeAll(bool $saveToDatabase = true): Collection
    {
        $allJobs = collect();

        foreach ($this->strategies as $companyName => $strategy) {
            Log::info("Starting scrape for {$companyName}");

            $result = $strategy->scrape();

            if ($result->success) {
                Log::info("Successfully scraped {$result->jobs->count()} jobs from {$companyName}");

                if ($saveToDatabase) {
                    $this->saveJobs($result->jobs);
                }

                $allJobs = $allJobs->merge($result->jobs);
            } else {
                Log::error("Failed to scrape {$companyName}: {$result->message}");
            }

            // Rate limiting
            sleep(config('scraping.delay_between_requests', 2));
        }

        return $allJobs;
    }

    public function scrapeCompany(string $companyName, array $filters = [], bool $saveToDatabase = true): Collection
    {
        if (!isset($this->strategies[$companyName])) {
            throw new \InvalidArgumentException("No strategy found for company: {$companyName}");
        }

        $strategy = $this->strategies[$companyName];

        $result = empty($filters)
            ? $strategy->scrape()
            : $strategy->scrapeWithFilters($filters);

        if ($result->success && $saveToDatabase) {
            $this->saveJobs($result->jobs);
        }

        return $result->jobs;
    }

    private function saveJobs(Collection $jobs): void
    {
        foreach ($jobs as $jobData) {
            JobListing::updateOrCreate(
                [
                    'external_id' => $jobData->externalId,
                    'company' => $jobData->company
                ],
                $jobData->toArray()
            );
        }
    }

    public function getScrapingStats(string $companyName = null, int $days = 7): array
    {
        $query = ScrapingLog::where('created_at', '>=', now()->subDays($days));

        if ($companyName) {
            $query->where('company', $companyName);
        }

        $logs = $query->get();

        return [
            'total_runs' => $logs->count(),
            'successful_runs' => $logs->where('status', 'success')->count(),
            'failed_runs' => $logs->where('status', 'failure')->count(),
            'total_jobs_found' => $logs->sum('jobs_found'),
            'total_jobs_saved' => $logs->sum('jobs_saved'),
            'average_duration' => $logs->where('duration_seconds')->avg('duration_seconds'),
            'last_successful_run' => $logs->where('status', 'success')->sortByDesc('completed_at')->first()?->completed_at
        ];
    }

    public function cleanupOldJobs(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        $deletedCount = JobListing::where('scraped_at', '<', $cutoffDate)->delete();

        Log::info("Cleaned up {$deletedCount} old job listings older than {$daysToKeep} days");

        return $deletedCount;
    }

    public function getAvailableCompanies(): array
    {
        return array_keys($this->strategies);
    }

    public function getCompanyFilters(string $companyName): array
    {
        if (!isset($this->strategies[$companyName])) {
            return [];
        }

        return $this->strategies[$companyName]->getAvailableFilters();
    }
}
