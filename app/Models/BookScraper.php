<?php

namespace App\Models;
use \DOMXPath;
use \DOMDocument;
use \DOMNode;


class BookScraper
{
    private DOMXpath|null $xpath;

    /**
     * @param string[] $urls
     */
    public function __construct(private array $urls)
    {
        $this->xpath = null;
    }

    /**
     * @return array|string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function process(): array
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
                $doc = new DOMDocument();
                @$doc->loadHTML($html);
                $this->xpath = new DOMXpath($doc);

                $this->parse();
            }
            return [ 'status' => 'completed' ];
        } catch ( \Exception $e ) {
            return [ 'status' => 'failed', 'error' => $e->getMessage() ];
        }
    }

    public function parse(): void
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

    private function extract(string $node, DOMNode $element): string {
        $value = $this->xpath->query($node, $element)->item(0)->nodeValue;
        return trim($value);
    }

    private function extractPrice(string $node, DOMNode $element): string {
        $str = $this->extract($node, $element);
        return trim(preg_replace('/[^0-9.]/', '', $str));
    }

    private function extractRating(string $node, DOMNode $element): string {
        $str = $this->extract($node, $element);
        return trim(str_replace('star-rating ', '', $str));
    }

    private function extractUrl(string $replace, string $str): string {
        $baseUri = 'https://books.toscrape.com/';
        return $baseUri . str_replace($replace, '', $str);
    }

    private function extractDetails(string $node, DOMNode $element): string {
        $str = $this->extract($node, $element);
        return $this->extractUrl('../../../', 'catalogue/' . $str);
    }

    private function extractImage(string $node, DOMNode $element): string {
        $str = $this->extract($node, $element);
        return $this->extractUrl('../../../../', $str);
    }
}
