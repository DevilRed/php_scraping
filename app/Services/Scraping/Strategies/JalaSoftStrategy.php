<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\Strategies\BaseScrapingStrategy;
use App\Services\Scraping\DTO\ScrapingResult;
use App\Services\Scraping\DTO\JobData;

class JalaSoftStrategy extends BaseScrapingStrategy
{
    public function getCompanyName(): string
    {
        return 'JalaSoft';
    }

    public function getBaseUrl(): string
    {
        return 'https://www.jalasoft.com';
    }

    public function scrape(): ScrapingResult
    {
        $url = 'https://www.jalasoft.com/careers/open-positions';
        $html = $this->makeRequest($url);

        if (!$html) {
            return ScrapingResult::failure('Failed to fetch page content');
        }

        try {
            $xpath = $this->createDomFromHtml($html);
            return $this->parseJobListings($xpath);
        } catch (\Exception $e) {
            return ScrapingResult::failure("Failed to scrape JalaSoft: " . $e->getMessage());
        }
    }

    private function parseJobListings(\DOMXPath $xpath): ScrapingResult
    {
        $jobs = [];

        // Multiple selectors to try
        $selectors = [
            '//a[contains(@href, "/careers") and contains(@href, "/job")]',
            '//a[contains(@href, "/position")]',
            '//div[contains(@class, "career") or contains(@class, "job")]//a',
            '//li[contains(@class, "position")]//a'
        ];

        foreach ($selectors as $selector) {
            $elements = $xpath->query($selector);

            if ($elements->length > 0) {
                foreach ($elements as $element) {
                    $href = $element->getAttribute('href');
                    $title = $this->extractTextContent($element);
                    $fullUrl = $this->makeAbsoluteUrl($href, $this->getBaseUrl());

                    // Extract additional info from parent context
                    $parentText = $this->extractTextContent($element->parentNode);
                    $location = $this->extractLocation($parentText);

                    $jobs[] = new JobData(
                        externalId: $this->generateJobId($fullUrl),
                        title: $title ?: 'Career Opportunity',
                        location: $location,
                        url: $fullUrl,
                        company: $this->getCompanyName(),
                        details: ['context' => $parentText, 'method' => 'static']
                    );
                }
                break;
            }
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'static']
        );
    }

    private function extractLocation(string $text): string
    {
        $locationPatterns = [
            '/Location:\s*([^-\n\r]+)/i',
            '/Remote|On-?site|Hybrid/i',
            '/(?:Santa Cruz|Cochabamba|La Paz|Bolivia|LATAM)/i'
        ];

        foreach ($locationPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1] ?? $matches[0]);
            }
        }

        return '';
    }
}
