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

    public function scrape(): ScrapingResult
    {
        $url = 'https://people.unosquare.com/jobs?filter=';

        try {
            // Try JSON endpoint first
            $html = $this->makeRequest($url, [
                'headers' => array_merge($this->defaultHeaders, [
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'X-Requested-With' => 'XMLHttpRequest'
                ])
            ]);

            if (!$html) {
                return ScrapingResult::failure('Failed to fetch page content');
            }

            // Try to detect if it's JSON response
            $jsonData = json_decode($html, true);
            if ($jsonData) {
                return $this->parseJsonJobs($jsonData);
            }

            // Fallback to HTML parsing
            $xpath = $this->createDomFromHtml($html);
            return $this->parseHtmlJobs($xpath);

        } catch (\Exception $e) {
            return ScrapingResult::failure("Failed to scrape Unosquare: " . $e->getMessage());
        }
    }

    private function parseJsonJobs(array $data): ScrapingResult
    {
        $jobs = [];

        // Adjust based on actual JSON structure
        $jobsData = $data['jobs'] ?? $data['positions'] ?? $data['data'] ?? $data;

        if (is_array($jobsData)) {
            foreach ($jobsData as $job) {
                $url = $job['url'] ?? $job['link'] ?? '';
                $jobs[] = new JobData(
                    externalId: $job['id'] ?? $this->generateJobId($url),
                    title: $job['title'] ?? $job['position'] ?? 'Unknown Position',
                    location: $job['location'] ?? $job['city'] ?? '',
                    url: $this->makeAbsoluteUrl($url, $this->getBaseUrl()),
                    company: $this->getCompanyName(),
                    details: $job
                );
            }
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
