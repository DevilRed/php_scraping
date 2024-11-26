<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BookScraper;

class BookScraperTest extends TestCase
{
    use RefreshDatabase;
    protected $bookScraper;
    protected function setUp(): void
    {
        parent::setUp();
        $url = ['category/books/nonfiction_13/index.html'];
        $this->bookScraper = new BookScraper($url);
    }
    #[Test]
    public function response_is_not_null(): void
    {
        $response = $this->bookScraper->process();
        $this->assertEquals($response, [ 'status' => 'completed' ]);
    }
}
