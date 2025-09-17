<?php

namespace App\Services\Scraping\Strategies\Dynamic;

use App\Services\Scraping\Strategies\BaseScrapingStrategy;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\WebDriverException;
use App\Services\Scraping\DTO\ScrapingResult;
use App\Services\Scraping\DTO\JobData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class ChromeDriverStrategy extends BaseScrapingStrategy
{
    protected RemoteWebDriver $driver;
    protected string $seleniumHost;
    protected int $implicitWait = 10;
    protected int $pageLoadTimeout = 30;

    public function __construct()
    {
        parent::__construct();
        $this->seleniumHost = config('scraping.selenium_host', 'http://localhost:4444/wd/hub');
    }

    protected function initializeDriver(): void
    {
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability('chromeOptions', [
            'args' => [
                '--headless',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--window-size=1920,1080'
            ]
        ]);

        try {
            $this->driver = RemoteWebDriver::create($this->seleniumHost, $capabilities);
            $this->driver->manage()->timeouts()->implicitlyWait($this->implicitWait);
            $this->driver->manage()->timeouts()->pageLoadTimeout($this->pageLoadTimeout);
        } catch (WebDriverException $e) {
            throw new \Exception("Failed to initialize Chrome driver: " . $e->getMessage());
        }
    }

    protected function closeDriver(): void
    {
        if (isset($this->driver)) {
            $this->driver->quit();
        }
    }

    protected function waitForElement(string $selector, int $timeout = 10)
    {
        $wait = new WebDriverWait($this->driver, $timeout);
        return $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector($selector)
        ));
    }

    protected function clickAndWait(string $selector, int $waitAfter = 2): void
    {
        $element = $this->waitForElement($selector);
        $element->click();
        sleep($waitAfter);
    }

    public function supportsFiltering(): bool
    {
        return true;
    }

    // Template method pattern for dynamic scraping
    public function scrape(): ScrapingResult
    {
        try {
            $this->initializeDriver();
            $this->driver->get($this->getScrapingUrl());

            $this->waitForPageLoad();
            $jobs = $this->extractJobs();

            $this->closeDriver();

            return ScrapingResult::success(
                collect($jobs),
                ['total_jobs' => count($jobs), 'method' => 'selenium']
            );

        } catch (\Exception $e) {
            $this->closeDriver();
            Log::error("Dynamic scraping failed for {$this->getCompanyName()}: " . $e->getMessage());

            return ScrapingResult::failure(
                "Dynamic scraping failed: " . $e->getMessage()
            );
        }
    }

    public function scrapeWithFilters(array $filters = []): ScrapingResult
    {
        try {
            $this->initializeDriver();
            $this->driver->get($this->getScrapingUrl());

            $this->waitForPageLoad();
            $this->applyFilters($filters);
            $jobs = $this->extractJobs();

            $this->closeDriver();

            return ScrapingResult::success(
                collect($jobs),
                [
                    'total_jobs' => count($jobs),
                    'method' => 'selenium',
                    'filters_applied' => $filters
                ]
            );

        } catch (\Exception $e) {
            $this->closeDriver();
            Log::error("Dynamic scraping with filters failed: " . $e->getMessage());

            return ScrapingResult::failure(
                "Filtered scraping failed: " . $e->getMessage()
            );
        }
    }

    // Abstract methods that each strategy must implement
    abstract protected function getScrapingUrl(): string;
    abstract protected function waitForPageLoad(): void;
    abstract protected function extractJobs(): array;
    abstract protected function applyFilters(array $filters): void;
}
