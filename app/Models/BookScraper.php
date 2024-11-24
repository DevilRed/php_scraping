<?php

namespace App\Models;


class BookScraper
{
    private $xpath;

    public function __construct(private $urls)
    {
        $this->xpath = null;
    }

    public function process()
    {
        try {
            $client = new \GuzzleHttp\Client(
                ['base_uri' => 'https://books.toscrape.com/catalogue/']
            );
            foreach ( $this->urls as $url ) {
                $result = $client->request('GET', $url);
                if ( $result->getStatusCode() != 200 ) { continue; }

                $html = $result->getBody()->getContents();

                // Load DOM Xpath
                $doc = new \DOMDocument();
                @$doc->loadHTML($html);
                $this->xpath = new \DOMXpath($doc);

                $this->parse();
            }
            return [ 'status' => 'completed' ];
        } catch ( \Exception $e ) {
            return [ 'status' => 'failed', 'error' => $e->getMessage() ];
        }
    }

    public function parse()
    {
        $books = $this->xpath->query("//ol[@class='row']/li");
        if ($books->length < 1) {
            throw new \Exception('No books returned for scraping the page!');
        }

        foreach ( $books as $book ) {
            $item                 = [];
            $item['title']        = $this->extract(".//h3//a/@title", $book);
            $item['price']        = $this->extractPrice(".//div[@class='product_price']//p[@class='price_color']", $book);
            $item['rating']       = $this->extractRating(".//p[contains(@class, 'star-rating')]/@class", $book);
            $item['in_stock']     = $this->extract(".//div[@class='product_price']//p[@class='instock availability']", $book);
            $item['details_url']  = $this->extractDetails(".//h3//a/@href", $book);
            $item['image_url']    = $this->extractImage(".//div[@class='image_container']//a//img/@src", $book);

            Book::firstOrCreate($item);
        }
    }

    private function extract($node, $element) {
        $value = $this->xpath->query($node, $element)->item(0)->nodeValue;
        return trim($value);
    }

    private function extractPrice($node, $element) {
        $str = $this->extract($node, $element);
        return trim(preg_replace('/[^0-9.]/', '', $str));
    }

    private function extractRating($node, $element) {
        $str = $this->extract($node, $element);
        return trim(str_replace('star-rating ', '', $str));
    }

    private function extractUrl($replace, $str) {
        $baseUri = 'https://books.toscrape.com/';
        return $baseUri . str_replace($replace, '', $str);
    }

    private function extractDetails($node, $element) {
        $str = $this->extract($node, $element);
        return $this->extractUrl('../../../', 'catalogue/' . $str);
    }

    private function extractImage($node, $element) {
        $str = $this->extract($node, $element);
        return $this->extractUrl('../../../../', $str);
    }
}
