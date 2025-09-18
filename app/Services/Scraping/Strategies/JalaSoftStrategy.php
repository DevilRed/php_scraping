<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\DTO\JobData;
use App\Services\Scraping\DTO\ScrapingResult;
use Exception;
use DOMXPath;

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
        } catch (Exception $e) {
            return ScrapingResult::failure("Failed to scrape JalaSoft: " . $e->getMessage());
        }
    }

    private function parseJobListings(DOMXPath $xpath): ScrapingResult
    {
        $jobs = [];

        // 1. Get all title nodes
        $titleNodes = $xpath->query("//div[contains(@class, 'BaseAccordionContent_TextColumn__ISNtN')]//p[contains(@class, 'TextV3_Body__Large__2KzsJ')]");

        // 2. Get all description nodes
        $descriptionNodes = $xpath->query("//div[contains(@class, 'BaseAccordionContent_ItemContainer__MJlFD')]");

        // 3. Pair them by index
        $jobCount = $titleNodes->length;
        for ($i = 0; $i < $jobCount; $i++) {
            $titleNode = $titleNodes->item($i);
            // The description might not exist for every title, so we check
            $descriptionNode = $descriptionNodes->item($i);

            if (!$titleNode) {
                continue;
            }

            $title = $this->extractTextContent($titleNode);
            $description = $descriptionNode ? $this->extractTextContent($descriptionNode) : '';

            // Since there's no unique URL per job, we create one based on the title
            $pseudoUrl = '#' . str_replace(' ', '-', strtolower($title));

            $jobs[] = new JobData(
                externalId: $this->generateJobId($title),
                title: $title,
                location: 'Bolivia', // Location seems to be static for this page
                url: $this->makeAbsoluteUrl($pseudoUrl, $this->getBaseUrl() . '/careers/open-positions'),
                company: $this->getCompanyName(),
                details: ['description' => $description, 'method' => 'static-paired']
            );
        }

        return ScrapingResult::success(
            collect($jobs),
            ['total_jobs' => count($jobs), 'method' => 'static-paired']
        );
    }
}
