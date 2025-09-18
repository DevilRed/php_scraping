<?php
use App\Services\Scraping\Strategies\UnosquareStrategy;
use Illuminate\Support\Facades\Http;

it('correctly scrapes job data from Unosquare JSON endpoint', function(){
    // 1. Arrange
    $mockJson = <<<'JSON'
    {
        "jobs": [
            {
                "id": 7738,
                "title": "7738 - React JavaScript",
                "location": "Bolivia, Colombia, Mexico, Paraguay",
                "url": "jobs/ReactJavaScript-7738"
            },
            {
                "id": 7730,
                "title": "7730 - React JavaScript",
                "location": "Argentina, Bolivia, Colombia, Mexico, Paraguay",
                "url": "jobs/ReactJavaScript-7730"
            }
        ]
    }
    JSON;

    // Fake the response for any URL.
    Http::fake([
        '*' => Http::response($mockJson, 200),
    ]);

    // Instantiate the class directly.
    $strategy = new UnosquareStrategy();

    // 2. Act
    $result = $strategy->scrape();

    // 3. Assert
    expect($result->wasSuccessful())->toBeTrue();

    $jobs = $result->getJobs();
    expect($jobs)->toHaveCount(2);

    // Assert first job
    expect($jobs[0]->title)->toBe('7738 - React JavaScript');
    expect($jobs[0]->location)->toBe('Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[0]->url)->toBe('https://people.unosquare.com/jobs/ReactJavaScript-7738');

    // Assert second job
    expect($jobs[1]->title)->toBe('7730 - React JavaScript');
    expect($jobs[1]->location)->toBe('Argentina, Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[1]->url)->toBe('https://people.unosquare.com/jobs/ReactJavaScript-7730');
});
