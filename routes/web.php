<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\JobScrapingController;
use App\Http\Controllers\JobListingController;

/*Route::get('/', [BookController::class, 'index']);
Route::post('/books/scrape', [BookController::class, 'scrape']);*/



Route::get('/', function () {
    return redirect()->route('jobs.dashboard');
});

// Job Scraping Dashboard
Route::prefix('jobs')->name('jobs.')->group(function () {
    Route::get('/', [JobListingController::class, 'index'])->name('index');
    Route::get('/dashboard', [JobScrapingController::class, 'dashboard'])->name('dashboard');

    // Scraping actions
    Route::post('/scrape', [JobScrapingController::class, 'scrape'])->name('scrape');
    Route::post('/scrape/{company}', [JobScrapingController::class, 'scrapeCompany'])->name('scrape.company');

    // AJAX endpoints
    Route::get('/api/stats', [JobScrapingController::class, 'stats'])->name('api.stats');
    Route::get('/api/logs', [JobScrapingController::class, 'logs'])->name('api.logs');
    Route::get('/api/filters/{company}', [JobScrapingController::class, 'filters'])->name('api.filters');

    // Job details
    Route::get('/show/{id}', [JobListingController::class, 'show'])->name('show');
    Route::delete('/delete/{id}', [JobListingController::class, 'destroy'])->name('destroy');
});
