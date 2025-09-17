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
                $jobs[] = new JobData(
                    externalId: $job['id'] ?? $this->generateJobId($job['url'] ?? ''),
                    title: $job['title'] ?? $job['position'] ?? 'Unknown Position',
                    location: $job['location'] ?? $job['city'] ?? '',
                    url: $job['url'] ?? $job['link'] ?? '',
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

        // Common selectors for job listings
        $selectors = [
            '//div[contains(@class, "job")]//a',
            '//article[contains(@class, "position")]//a',
            '//li[contains(@class, "job-item")]//a',
            '//a[contains(@href, "/job") or contains(@href, "/position")]'
        ];

        foreach ($selectors as $selector) {
            $jobElements = $xpath->query($selector);

            if ($jobElements->length > 0) {
                foreach ($jobElements as $element) {
                    $href = $element->getAttribute('href');
                    $title = $this->extractTextContent($element);
                    $fullUrl = $this->makeAbsoluteUrl($href, $this->getBaseUrl());

                    // Try to find location in parent elements
                    $location = $this->findLocationInParents($element);

                    $jobs[] = new JobData(
                        externalId: $this->generateJobId($fullUrl),
                        title: $title ?: 'Position Available',
                        location: $location,
                        url: $fullUrl,
                        company: $this->getCompanyName(),
                        details: ['method' => 'html']
                    );
                }
                break; // Stop after finding jobs with first successful selector
            }
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'html']
        );
    }

    private function findLocationInParents(DOMNode $node): string
    {
        $current = $node;
        $maxLevels = 3;

        while ($current->parentNode && $maxLevels > 0) {
            $current = $current->parentNode;
            $text = $this->extractTextContent($current);

            // Look for location patterns
            if (preg_match('/(?:Location:|Remote|Office:)\s*([^-\n\r]+)/i', $text, $matches)) {
                return trim($matches[1]);
            }

            $maxLevels--;
        }

        return '';
    }
}
