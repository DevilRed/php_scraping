<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScrapingBookTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_books_are_found(): void
    {
        $client = new \GuzzleHttp\Client(
            [
                'base_uri' => 'https://books.toscrape.com/catalogue/',
                'verify' => false
            ]
        );
        $result = $client->request('GET', 'category/books/nonfiction_13/index.html');
        $html = $result->getBody()->getContents();
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);// @ before variable to suppress errors
        $xpath = new \DOMXpath($doc);

        $books = $xpath->query("//ol[@class='row']/li");
        $this->assertNotNull($books);


    }
}
