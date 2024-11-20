<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Scraper</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container px-4 py-5">
    <header class="d-flex mb-4">
        <h1 class="me-auto">Books</h1>
        <form method="post" action="/books/scrape">
            @csrf
            <button class="btn btn-primary" type="submit">Scrape Books</button>
        </form>
    </header>

    @if (count($books) >= 1)
        <p>Total {{ count($books) }} books scraped!</p>
        <table class="table">
            <thead>
            <tr>
                <th width="100">Image</th>
                <th width="50%">Title</th>
                <th>Price</th>
                <th>Rating</th>
                <th>In Stock</th>
                <th>Details</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($books as $book)
                <tr>
                    <td><img src="{{ $book->image_url }}" height="80"/></td>
                    <td>{{ $book->title }}</td>
                    <td>£{{ $book->price }}</td>
                    <td>{{ $book->rating }}</td>
                    <td>{{ $book->in_stock }}</td>
                    <td><a href="{{ $book->details_url }}" target="_blank">View Details</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>No books available yet!</p>
    @endif
</div>
</body>
</html>
