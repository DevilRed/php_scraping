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
        $jobLinks = $xpath->query('//a[contains(@href, "/careers/open-positions/jobs/")]');

        $jobs = [];
        foreach ($jobLinks as $link) {
            $href = $link->getAttribute('href');
            $parentText = $this->extractTextContent($link->parentNode);

            // Extract location
            $location = '';
            if (preg_match('/Location:\s*(\S+)/i', $parentText, $matches)) {
                $location = trim($matches[1]);
            }

            // Extract title
            $title = $this->extractTextContent($link);
            if (empty($title) || $title === 'View job') {
                if (preg_match('/([^-\n\r]+)\s*-\s*Location:/', $parentText, $matches)) {
                    $title = trim($matches[1]);
                } else {
                    $title = 'Position Available';
                }
            }

            $jobs[] = new JobData(
                externalId: $this->generateJobId($href),
                title: $title,
                location: $location,
                url: $this->makeAbsoluteUrl($href, $this->getBaseUrl()),
                company: $this->getCompanyName(),
                details: ['raw_text' => $parentText, 'method' => 'static']
            );
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'static']
        );
    }
}
