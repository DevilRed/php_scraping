<?php

namespace App\Services\Scraping\DTO;

use Illuminate\Support\Collection;

class ScrapingResult
{
    public function __construct(
        public Collection $jobs,
        public bool $success,
        public string $message = '',
        public array $metadata = []
    ) {}

    public static function success(Collection $jobs, array $metadata = []): self
    {
        return new self($jobs, true, 'Scraping completed successfully', $metadata);
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(collect(), false, $message, $metadata);
    }
}
