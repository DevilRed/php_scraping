<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\Strategies\BaseScrapingStrategy;
use App\Services\Scraping\DTO\ScrapingResult;
use App\Services\Scraping\DTO\JobData;
use Illuminate\Support\Collection;

class AssureSoftStrategy extends BaseScrapingStrategy
{
    public function getCompanyName(): string
    {
        return 'AssureSoft';
    }

    public function getBaseUrl(): string
    {
        return 'https://www.assuresoft.com';
    }

    public function supportsFiltering(): bool
    {
        return true;
    }

    public function getAvailableFilters(): array
    {
        return [
            'location' => ['Bolivia', 'LATAM', 'Remote'],
            'department' => ['Engineering', 'QA', 'Product', 'Sales'],
            'experience' => ['Junior', 'Mid-level', 'Senior']
        ];
    }

    public function scrape(): ScrapingResult
    {
        // Try static scraping first (more reliable for basic testing)
        return $this->scrapeStatic();
    }

    public function scrapeWithFilters(array $filters = []): ScrapingResult
    {
        // For now, ignore filters and do basic scraping
        // In a real implementation, you would use Selenium to interact with filters
        return $this->scrape();
    }

    private function scrapeStatic(): ScrapingResult
    {
        $url = 'https://www.assuresoft.com/careers/open-positions';
        $html = $this->makeRequest($url);

        if (!$html) {
            return ScrapingResult::failure('Failed to fetch page content');
        }

        $xpath = $this->createDomFromHtml($html);
        $jobNodes = $xpath->query('//div[contains(@class, "job-info")]');

        $jobs = [];
        foreach ($jobNodes as $jobNode) {
            // Extract title
            $titleNode = $xpath->query('.//span[contains(@class, "job-title-card")]/strong', $jobNode)->item(0);
            $title = $titleNode ? trim($this->extractTextContent($titleNode)) : 'Position Available';

            // Extract location
            $locationNode = $xpath->query('.//li[contains(@class, "job-location-card")]/span[@class="country"]', $jobNode)->item(0);
            $location = $locationNode ? trim($this->extractTextContent($locationNode)) : '';

            // Extract URL
            $linkNode = $xpath->query('.//a[contains(@href, "/careers/open-positions/jobs/")]', $jobNode)->item(0);
            $href = $linkNode ? $linkNode->getAttribute('href') : '';

            if (empty($href)) {
                continue; // Skip if no valid job link is found
            }

            $jobs[] = new JobData(
                externalId: $this->generateJobId($href),
                title: $title,
                location: $location,
                url: $this->makeAbsoluteUrl($href, $this->getBaseUrl()),
                company: $this->getCompanyName(),
                details: ['method' => 'static']
            );
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'static']
        );
    }
}
