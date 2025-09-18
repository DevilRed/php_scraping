<?php

namespace App\Services\Scraping\Strategies;

use App\Services\Scraping\Contracts\ScrapingStrategyInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use DOMDocument;
use DOMXPath;
use DOMNode;
use Illuminate\Support\Facades\Log;
use App\Services\Scraping\DTO\ScrapingResult;

abstract class BaseScrapingStrategy implements ScrapingStrategyInterface
{
    protected array $defaultHeaders;
    protected int $timeout = 30;

    public function __construct()
    {
        $this->defaultHeaders = [
            'User-Agent' => config('scraping.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive'
        ];
    }
    protected function createDomFromHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    protected function extractTextContent(DOMNode $node): string
    {
        return trim(preg_replace('/\s+/', ' ', $node->textContent));
    }

    protected function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    protected function generateJobId(string $url): string
    {
        if (preg_match('/\/(\d+)(?:\D|$)/', $url, $matches)) {
            return $matches[1];
        }

        return md5($url);
    }

    protected function makeRequest(string $url, array $options = []): ?string
    {
        try {
            $headers = array_merge($this->defaultHeaders, $options['headers'] ?? []);

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($url);

            $response->throw();

            return $response->body();
        } catch (RequestException $e) {
            Log::error("HTTP request failed for {$url}: " . $e->getMessage());
            return null;
        }
    }

    // Default implementations
    public function supportsFiltering(): bool
    {
        return false;
    }

    public function getAvailableFilters(): array
    {
        return [];
    }

    public function scrapeWithFilters(array $filters = []): ScrapingResult
    {
        return $this->scrape();
    }
}
