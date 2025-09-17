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
        // Register strategies - could be moved to config or service provider
        $this->registerStrategy(app(AssureSoftStrategy::class));
        // Add other strategies here
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
