<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\Strategies\BaseScrapingStrategy;
use App\Services\Scraping\DTO\ScrapingResult;
use App\Services\Scraping\DTO\JobData;
use DOMNode;

class UnosquareStrategy extends BaseScrapingStrategy
{
    public function getCompanyName(): string
    {
        return 'Unosquare';
    }

    public function getBaseUrl(): string
    {
        return 'https://people.unosquare.com';
    }

    public function scrape($filters = null): ScrapingResult
    {
        // The main jobs page contains a JSON blob with all the data we need.
        // This is more robust than hitting a data URL that might have a changing build ID.
        $url = 'https://people.unosquare.com/jobs';

        try {
            $html = $this->makeRequest($url, [
                'headers' => $this->defaultHeaders
            ]);

            if (!$html) {
                return ScrapingResult::failure('Failed to fetch page content');
            }

            $xpath = $this->createDomFromHtml($html);
            $scriptNode = $xpath->query('//script[@id="__NEXT_DATA__"]')->item(0);

            if (!$scriptNode) {
                // Fallback to parsing the visible HTML if the JSON script isn't there.
                return $this->parseHtmlJobs($xpath);
            }

            $jsonData = json_decode($scriptNode->textContent, true);

            if ($jsonData) {
                return $this->parseJsonJobs($jsonData, $filters);
            }

            // If we found the script but couldn't parse it, or it was empty.
            return ScrapingResult::failure('Failed to parse __NEXT_DATA__ JSON from page.');

        } catch (\Exception $e) {
            return ScrapingResult::failure("Failed to scrape Unosquare: " . $e->getMessage());
        }
    }

    private function parseJsonJobs(array $data, ?string $filters = null): ScrapingResult
    {
        $jobs = [];

        $jobsData = $data['pageProps']['allJobsData'] ?? [];

        $filteredJobs = $jobsData;
        if ($filters) {
            $filterKeywords = explode('&', $filters);
            $filterKeywords = array_map('trim', $filterKeywords);
            $filterKeywords = array_map('strtolower', $filterKeywords);
            $filterKeywords = array_filter($filterKeywords);

            if (!empty($filterKeywords)) {
                $filteredJobs = [];
                foreach ($jobsData as $job) {
                    $searchText = strtolower(
                        ($job['JobTitle'] ?? '') . ' ' .
                        ($job['MainSkill'] ?? '') . ' ' .
                        ($job['JobDescription'] ?? '')
                    );

                    foreach ($filterKeywords as $keyword) {
                        if (str_contains($searchText, $keyword)) {
                            $filteredJobs[] = $job;
                            break; // Job matches, move to next job
                        }
                    }
                }
            }
        }

        foreach ($filteredJobs as $job) {
            $url = '';
            $titleWithId = $job['JobTitleWithId'] ?? null;
            if ($titleWithId) {
                // Create a slug from "ID - Title" e.g. "7844-business-analyst-product-administration"
                $slug = strtolower($titleWithId);
                $slug = str_replace(' - ', '-', $slug);
                $slug = str_replace(' ', '-', $slug);
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                $url = "/jobs/{$slug}";
            }

            $jobs[] = new JobData(
                externalId: (string)($job['CareerOpportunityId'] ?? $this->generateJobId($url)),
                title: $job['JobTitle'] ?? 'Unknown Position',
                location: $job['OfficeLocation'] ?? '',
                url: $this->makeAbsoluteUrl($url, $this->getBaseUrl()),
                company: $this->getCompanyName(),
                details: $job
            );
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'json']
        );
    }

    private function parseHtmlJobs(\DOMXPath $xpath): ScrapingResult
    {
        $jobs = [];
        // Anchor on the most specific element: the job title div with its exact classes.
        $titleNodes = $xpath->query('//div[@class="font-bold text-xl font-oswald pt-1"]');

        foreach ($titleNodes as $titleNode) {
            // Find the main container by traversing up from the title
            $containerNode = $xpath->query('ancestor::a[contains(@class, "shadow-jobContainer")]', $titleNode)->item(0);

            if (!$containerNode) {
                continue;
            }

            $href = $containerNode->getAttribute('href');
            $title = $this->extractTextContent($titleNode);

            $locationNode = $xpath->query('.//div[contains(@class, "text-xs pb-1")]/label', $containerNode)->item(0);
            $location = $locationNode ? $this->extractTextContent($locationNode) : '';

            if (empty($title) || empty($href) || !str_contains($href, 'jobs/')) {
                continue;
            }

            $jobs[] = new JobData(
                externalId: $this->generateJobId($href),
                title: $title,
                location: $location,
                url: $this->makeAbsoluteUrl($href, $this->getBaseUrl()),
                company: $this->getCompanyName(),
                details: ['method' => 'html']
            );
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'html']
        );
    }
}
