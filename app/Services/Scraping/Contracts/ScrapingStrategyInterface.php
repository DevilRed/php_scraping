<?php
namespace App\Services\Scraping\Contracts;

use App\Services\DTO\ScrapingResult;

interface ScrapingStrategyInterface {
    public function scrape(): ScrapingResult;
    public function getCompanyName(): string;
    public function getBaseUrl(): string;
    public function supportsFiltering(): bool;
    public function getAvailableFilters(): array;
    public function scrapeWithFilters(array $filters = []): ScrapingResult;
}
