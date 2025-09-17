<?php

namespace App\Services;

use App\Models\JobListing;
use App\Services\Scraping\Contracts\ScrapingStrategyInterface;
use App\Services\Scraping\Strategies\AssureSoftStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
class JobScrapingService
{
    private array $strategies = [];

    public function __construct()
    {
        $this->registerStrategy(app(AssureSoftStrategy::class));
    }

    public function registerStrategy(ScrapingStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getCompanyName()] = $strategy;
    }

    public function scrapeAll(bool $saveToDatabase = true): Collection
    {
        $allJobs = collect();

        foreach ($this->strategies as $companyName => $strategy) {
            $scrapingLog = $this->startScrapingLog($companyName);

            try {
                Log::info("Starting scrape for {$companyName}");

                $result = $strategy->scrape();

                if ($result->success) {
                    Log::info("Successfully scraped {$result->jobs->count()} jobs from {$companyName}");

                    $jobsSaved = 0;
                    if ($saveToDatabase) {
                        $jobsSaved = $this->saveJobs($result->jobs);
                    }

                    $this->completeScrapingLog($scrapingLog, 'success', [
                        'jobs_found' => $result->jobs->count(),
                        'jobs_saved' => $jobsSaved,
                        'metadata' => $result->metadata
                    ]);

                    $allJobs = $allJobs->merge($result->jobs);
                } else {
                    Log::error("Failed to scrape {$companyName}: {$result->message}");

                    $this->completeScrapingLog($scrapingLog, 'failure', [
                        'error_message' => $result->message,
                        'metadata' => $result->metadata
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Exception during {$companyName} scraping: " . $e->getMessage());

                $this->completeScrapingLog($scrapingLog, 'failure', [
                    'error_message' => $e->getMessage()
                ]);
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

        $scrapingLog = $this->startScrapingLog($companyName, $filters);

        try {
            $strategy = $this->strategies[$companyName];

            $result = empty($filters)
                ? $strategy->scrape()
                : $strategy->scrapeWithFilters($filters);

            $jobsSaved = 0;
            if ($result->success && $saveToDatabase) {
                $jobsSaved = $this->saveJobs($result->jobs);
            }

            $this->completeScrapingLog($scrapingLog, $result->success ? 'success' : 'failure', [
                'jobs_found' => $result->jobs->count(),
                'jobs_saved' => $jobsSaved,
                'error_message' => $result->success ? null : $result->message,
                'metadata' => $result->metadata
            ]);

            return $result->jobs;

        } catch (\Exception $e) {
            $this->completeScrapingLog($scrapingLog, 'failure', [
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function saveJobs(Collection $jobs): int
    {
        $savedCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        foreach ($jobs as $jobData) {
            try {
                DB::beginTransaction();

                $existingJob = JobListing::where('external_id', $jobData->externalId)
                    ->where('company', $jobData->company)
                    ->first();

                if ($existingJob) {
                    // Update existing job if content has changed
                    $hasChanges = $this->hasJobChanges($existingJob, $jobData);

                    if ($hasChanges) {
                        $existingJob->update($jobData->toArray());
                        $savedCount++;
                        Log::debug("Updated job: {$jobData->title} at {$jobData->company}");
                    } else {
                        // Just update the scraped_at timestamp
                        $existingJob->touch('scraped_at');
                        $duplicateCount++;
                    }
                } else {
                    // Create new job listing
                    JobListing::create($jobData->toArray());
                    $savedCount++;
                    Log::debug("Created new job: {$jobData->title} at {$jobData->company}");
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to save job {$jobData->title}: " . $e->getMessage());
                $errorCount++;
            }
        }

        Log::info("Job save summary - Saved: {$savedCount}, Duplicates: {$duplicateCount}, Errors: {$errorCount}");

        return $savedCount;
    }

    private function hasJobChanges(JobListing $existingJob, JobData $newJobData): bool
    {
        $fieldsToCheck = ['title', 'location', 'description', 'requirements', 'salary', 'employment_type'];

        foreach ($fieldsToCheck as $field) {
            $existingValue = $existingJob->{$field} ?? '';
            $newValue = $newJobData->{$field} ?? '';

            if (trim($existingValue) !== trim($newValue)) {
                return true;
            }
        }

        return false;
    }

    private function startScrapingLog(string $companyName, array $filters = []): ScrapingLog
    {
        return ScrapingLog::create([
            'company' => $companyName,
            'status' => 'running',
            'filters_applied' => $filters,
            'started_at' => now()
        ]);
    }

    private function completeScrapingLog(ScrapingLog $log, string $status, array $data = []): void
    {
        $completedAt = now();
        $durationSeconds = $completedAt->diffInSeconds($log->started_at);

        $log->update(array_merge([
            'status' => $status,
            'completed_at' => $completedAt,
            'duration_seconds' => $durationSeconds
        ], $data));
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
