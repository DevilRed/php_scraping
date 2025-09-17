<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\Strategies\Dynamic\ChromeDriverStrategy;
use App\Services\Scraping\DTO\ScrapingResult;
use App\Services\Scraping\DTO\JobData;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Collection;

class AssureSoftStrategy extends ChromeDriverStrategy
{
    public function getCompanyName(): string
    {
        return 'AssureSoft';
    }

    public function getBaseUrl(): string
    {
        return 'https://www.assuresoft.com';
    }

    protected function getScrapingUrl(): string
    {
        return 'https://www.assuresoft.com/careers/open-positions';
    }

    protected function waitForPageLoad(): void
    {
        $this->waitForElement('a[href*="/careers/open-positions/jobs/"]', 15);
        sleep(2); // Additional wait for any dynamic loading
    }

    protected function extractJobs(): array
    {
        $jobElements = $this->driver->findElements(
            WebDriverBy::cssSelector('a[href*="/careers/open-positions/jobs/"]')
        );

        $jobs = [];
        foreach ($jobElements as $element) {
            $href = $element->getAttribute('href');
            $parentText = $element->findElement(WebDriverBy::xpath('..'))->getText();

            // Extract location
            $location = '';
            if (preg_match('/Location:\s*([^-\n\r]+)/i', $parentText, $matches)) {
                $location = trim($matches[1]);
            }

            // Extract title
            $title = $element->getText();
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
                details: ['raw_text' => $parentText]
            );
        }

        return $jobs;
    }

    protected function applyFilters(array $filters): void
    {
        // Look for filter elements - adjust selectors based on actual page structure
        if (isset($filters['location'])) {
            try {
                $locationFilter = $this->driver->findElement(
                    WebDriverBy::cssSelector('[data-filter="location"], .location-filter')
                );
                $locationFilter->click();
                sleep(1);

                // Select specific location option
                $locationOption = $this->driver->findElement(
                    WebDriverBy::xpath("//option[contains(text(), '{$filters['location']}')]")
                );
                $locationOption->click();
                sleep(2);
            } catch (\Exception $e) {
                // Log but continue if filter not found
                \Log::warning("Location filter not found: " . $e->getMessage());
            }
        }

        if (isset($filters['department'])) {
            try {
                $departmentFilter = $this->driver->findElement(
                    WebDriverBy::cssSelector('[data-filter="department"], .department-filter')
                );
                $departmentFilter->click();
                sleep(1);

                $departmentOption = $this->driver->findElement(
                    WebDriverBy::xpath("//option[contains(text(), '{$filters['department']}')]")
                );
                $departmentOption->click();
                sleep(2);
            } catch (\Exception $e) {
                \Log::warning("Department filter not found: " . $e->getMessage());
            }
        }
    }

    public function getAvailableFilters(): array
    {
        return [
            'location' => ['Bolivia', 'LATAM', 'Remote'],
            'department' => ['Engineering', 'QA', 'Product', 'Sales'],
            'experience' => ['Junior', 'Mid-level', 'Senior']
        ];
    }

    // Fallback to static scraping if Selenium fails
    public function scrape(): ScrapingResult
    {
        // Try dynamic scraping first
        $dynamicResult = parent::scrape();

        if ($dynamicResult->success) {
            return $dynamicResult;
        }

        // Fallback to static scraping
        return $this->scrapeStatic();
    }

    private function scrapeStatic(): ScrapingResult
    {
        $html = $this->makeRequest($this->getScrapingUrl());

        if (!$html) {
            return ScrapingResult::failure('Failed to fetch page content');
        }

        $xpath = $this->createDomFromHtml($html);
        $jobLinks = $xpath->query('//a[contains(@href, "/careers/open-positions/jobs/")]');

        $jobs = [];
        foreach ($jobLinks as $link) {
            $href = $link->getAttribute('href');
            $parentText = $this->extractTextContent($link->parentNode);

            $location = '';
            if (preg_match('/Location:\s*([^-\n\r]+)/i', $parentText, $matches)) {
                $location = trim($matches[1]);
            }

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
            ['total_jobs' => count($jobs), 'method' => 'static_fallback']
        );
    }
}
