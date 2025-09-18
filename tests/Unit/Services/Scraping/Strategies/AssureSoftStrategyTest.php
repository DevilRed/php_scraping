<?php

use App\Services\Scraping\DTO\JobData;
use App\Services\Scraping\Strategies\AssureSoftStrategy;
use Illuminate\Support\Facades\Http;

it('correctly scrapes job data from static html', function () {
    // 1. Arrange
    $mockHtml = <<<'HTML'
        <div class="job-info">
          <span class="job-title-card"><strong> PHP Software Developer </strong></span>
          <li class="job-location-card">
            <span> Location: </span>
            <span class="country">Bolivia</span>
          </li>
                            <li>
            <a class="btn c-btn-persian-blue c-btn-bold" href="/careers/open-positions/jobs/e_board/4599879006"> View job </a>
          </li>
        </div>

        <div class="job-info">
          <span class="job-title-card"><strong> Java Spring Developer </strong></span>
          <li class="job-location-card">
            <span> Location: </span>
            <span class="country">Bolivia</span>
          </li>
                            <li>
            <a class="btn c-btn-persian-blue c-btn-bold" href="/careers/open-positions/jobs/e_board/4598020006"> View job </a>
          </li>
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
    expect($jobs[0]->title)->toBe('PHP Software Developer');
    expect($jobs[0]->location)->toBe('Bolivia');
    expect($jobs[0]->url)->toBe('https://www.assuresoft.com//careers/open-positions/jobs/e_board/4599879006');

    // Assert second job
    expect($jobs[1]->title)->toBe('Java Spring Developer');
    expect($jobs[1]->location)->toBe('LATAM');
    expect($jobs[1]->url)->toBe('https://www.assuresoft.com/careers/open-positions/jobs/e_board/4598020006');
});
