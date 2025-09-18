<?php

use App\Services\Scraping\DTO\JobData;
use App\Services\Scraping\Strategies\AssureSoftStrategy;
use Illuminate\Support\Facades\Http;

it('correctly scrapes job data from static html', function () {
    // 1. Arrange
    $mockHtml = <<<'HTML'
        <div>
            Senior QA Engineer - Location: Bolivia
            <a href="/careers/open-positions/jobs/senior-qa-engineer">View job</a>
        </div>
        <div>
            Junior Developer - Location: LATAM
            <a href="/careers/open-positions/jobs/junior-developer">View job</a>
        </div>
    HTML;

    // Mock the strategy and allow mocking of protected methods
    $strategy = Mockery::mock(AssureSoftStrategy::class)->makePartial();
    $strategy->shouldAllowMockingProtectedMethods();
    $strategy->shouldReceive('makeRequest')->andReturn($mockHtml);

    // 2. Act
    $result = $strategy->scrape();

    // 3. Assert
    expect($result->wasSuccessful())->toBeTrue();

    $jobs = $result->getJobs();
    expect($jobs)->toHaveCount(2);

    // Assert first job
    expect($jobs[0]->title)->toBe('Senior QA Engineer');
    expect($jobs[0]->location)->toBe('Bolivia');
    expect($jobs[0]->url)->toBe('https://www.assuresoft.com/careers/open-positions/jobs/senior-qa-engineer');

    // Assert second job
    expect($jobs[1]->title)->toBe('Junior Developer');
    expect($jobs[1]->location)->toBe('LATAM');
    expect($jobs[1]->url)->toBe('https://www.assuresoft.com/careers/open-positions/jobs/junior-developer');
});
