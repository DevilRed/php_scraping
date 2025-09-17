<?php

use PHPUnit\Framework\Attributes\Test;
use App\Models\BookScraper;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $url = ['category/books/nonfiction_13/index.html'];
    $this->bookScraper = new BookScraper($url);
});
test('response is not null', function () {
    $response = $this->bookScraper->process();
    expect([ 'status' => 'completed' ])->toEqual($response);
});