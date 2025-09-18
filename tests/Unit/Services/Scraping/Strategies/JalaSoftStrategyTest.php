<?php

use App\Services\Scraping\Strategies\JalaSoftStrategy;
use Illuminate\Support\Facades\Http;

it('correctly scrapes accordion-style job data from JalaSoft', function () {
    // 1. Arrange
    $mockHtml = <<<'HTML'
        <div>
            <!-- Section with all the titles -->
            <div class="BaseAccordionContent_TextColumn__ISNtN">
                <div class="Items_Container__Mt8Nv">
                    <p class="TextV3_Default__QGxuu TextV3_Body__Large__2KzsJ">Senior Python Developer with AWS</p>
                </div>
                <div class="Items_Container__Mt8Nv">
                    <p class="TextV3_Default__QGxuu TextV3_Body__Large__2KzsJ">Semi Senior C++ Developer</p>
                </div>
            </div>

            <!-- Section with all the descriptions (using a more realistic structure) -->
            <div>
                <div class="BaseAccordionContent_ItemContainer__MJlFD">
                    <div class="TextV3_Default__QGxuu TextV3_Body__Large__2KzsJ">
                        <p><strong>Must have:</strong></p>
                        <ul><li>5+ years of professional experience in backend development with Python.</li><li>Proven experience with AWS ECS, RDS, and S3.</li></ul>
                    </div>
                </div>
                <div class="BaseAccordionContent_ItemContainer__MJlFD">
                    <div class="TextV3_Default__QGxuu TextV3_Body__Large__2KzsJ">
                        <p><strong>Must have</strong></p>
                        <ul><li>5+ years of hands-on experience with C++.</li><li>Strong understanding of networking concepts.</li></ul>
                        <p><strong>Nice to have</strong></p><ul><li>Experience with additional programming languages: Python, Java, or C#.</li></ul>
                    </div>
                </div>
            </div>
        </div>
    HTML;

    Http::fake([
        '*' => Http::response($mockHtml, 200),
    ]);

    $strategy = new JalaSoftStrategy();

    // 2. Act
    $result = $strategy->scrape();

    // 3. Assert
    expect($result->wasSuccessful())->toBeTrue();

    $jobs = $result->getJobs();
    expect($jobs)->toHaveCount(2);

    // Assert first job is paired correctly with the more detailed description
    expect($jobs[0]->title)->toBe('Senior Python Developer with AWS');
    expect($jobs[0]->details['description'])->toContain('5+ years of professional experience in backend development with Python');
    expect($jobs[0]->url)->toContain('#senior-python-developer-with-aws');

    // Assert second job is paired correctly
    expect($jobs[1]->title)->toBe('Semi Senior C++ Developer');
    expect($jobs[1]->details['description'])->toContain('5+ years of hands-on experience with C++');
    expect($jobs[1]->details['description'])->toContain('Nice to have');
    expect($jobs[1]->url)->toContain('#semi-senior-c++-developer');
});
