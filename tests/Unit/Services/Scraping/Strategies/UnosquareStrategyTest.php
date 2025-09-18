<?php
use App\Services\Scraping\Strategies\UnosquareStrategy;
use Illuminate\Support\Facades\Http;

it('correctly scrapes job data from Unosquare page', function() {
    // 1. Arrange
    $mockJobs = [
        [
            'CareerOpportunityId' => 7738,
            'JobTitle' => 'React JavaScript',
            'JobTitleWithId' => '7738 - React JavaScript',
            'OfficeLocation' => 'Bolivia, Colombia, Mexico, Paraguay',
            'MainSkill' => 'React',
            'JobDescription' => 'A job requiring React and JavaScript skills.'
        ],
        [
            'CareerOpportunityId' => 7730,
            'JobTitle' => 'React JavaScript',
            'JobTitleWithId' => '7730 - React JavaScript',
            'OfficeLocation' => 'Argentina, Bolivia, Colombia, Mexico, Paraguay',
            'MainSkill' => 'React',
            'JobDescription' => 'Another job requiring React and JavaScript skills.'
        ],
    ];
    $nextData = json_encode(['pageProps' => ['allJobsData' => $mockJobs]]);
    $mockHtml = <<<HTML
    <!DOCTYPE html><html><body><script id="__NEXT_DATA__" type="application/json">{$nextData}</script></body></html>
    HTML;

    Http::fake(['*' => Http::response($mockHtml, 200)]);

    $strategy = new UnosquareStrategy();

    // 2. Act
    $result = $strategy->scrape();

    // 3. Assert
    expect($result->wasSuccessful())->toBeTrue();

    $jobs = $result->getJobs();
    expect($jobs)->toHaveCount(2);

    // Assert first job
    expect($jobs[0]->title)->toBe('React JavaScript');
    expect($jobs[0]->location)->toBe('Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[0]->url)->toBe('https://people.unosquare.com/jobs/7738-react-javascript');

    // Assert second job
    expect($jobs[1]->title)->toBe('React JavaScript');
    expect($jobs[1]->location)->toBe('Argentina, Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[1]->url)->toBe('https://people.unosquare.com/jobs/7730-react-javascript');
});

it('shows proper result when filters are applied', function() {
    // 1. Arrange
    $mockJobs = [
        [
            'CareerOpportunityId' => 7738,
            'JobTitle' => 'React Developer',
            'JobTitleWithId' => '7738 - React Developer',
            'OfficeLocation' => 'Bolivia, Colombia, Mexico, Paraguay',
            'MainSkill' => 'React',
            'JobDescription' => 'A job requiring React and JavaScript skills.'
        ],
        [
            'CareerOpportunityId' => 7730,
            'JobTitle' => 'Java Developer',
            'JobTitleWithId' => '7730 - Java Developer',
            'OfficeLocation' => 'Argentina, Bolivia, Colombia, Mexico, Paraguay',
            'MainSkill' => 'Java',
            'JobDescription' => 'A job requiring Java skills.'
        ],
        [
            'CareerOpportunityId' => 7729,
            'JobTitle' => 'Python Developer',
            'JobTitleWithId' => '7729 - Python Developer',
            'OfficeLocation' => 'Mexico',
            'MainSkill' => 'Python',
            'JobDescription' => 'A job requiring Python skills.'
        ],
    ];
    $nextData = json_encode(['pageProps' => ['allJobsData' => $mockJobs]]);
    $mockHtml = <<<HTML
    <!DOCTYPE html><html><body><script id="__NEXT_DATA__" type="application/json">{$nextData}</script></body></html>
    HTML;

    Http::fake(['*' => Http::response($mockHtml, 200)]);

    $strategy = new UnosquareStrategy();

    // 2. Act
    $filters="Javascript&Java"; // Filter for Javascript OR Java
    $result = $strategy->scrape($filters);

    // 3. Assert
    expect($result->wasSuccessful())->toBeTrue();

    $jobs = $result->getJobs();
    expect($jobs)->toHaveCount(2); // React Developer (has Javascript in desc) and Java Developer

    // Assert first job (React/JS)
    expect($jobs[0]->title)->toBe('React Developer');
    expect($jobs[0]->location)->toBe('Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[0]->url)->toBe('https://people.unosquare.com/jobs/7738-react-developer');

    // Assert second job (Java)
    expect($jobs[1]->title)->toBe('Java Developer');
    expect($jobs[1]->location)->toBe('Argentina, Bolivia, Colombia, Mexico, Paraguay');
    expect($jobs[1]->url)->toBe('https://people.unosquare.com/jobs/7730-java-developer');
});
