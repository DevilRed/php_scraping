<?php

test('books are found', function () {
    $client = new \GuzzleHttp\Client(
        [
            'base_uri' => 'https://books.toscrape.com/catalogue/',
            'verify' => false
        ]
    );
    $result = $client->request('GET', 'category/books/nonfiction_13/index.html');
    $html = $result->getBody()->getContents();
    $doc = new \DOMDocument();
    @$doc->loadHTML($html);
    // @ before variable to suppress errors
    $xpath = new \DOMXpath($doc);

    $books = $xpath->query("//ol[@class='row']/li");
    $books = ($books === false) ? null : $books;
    expect($books)->not->toBeNull();
});
