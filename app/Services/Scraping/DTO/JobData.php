<?php

namespace App\Services\Scraping\DTO;

class JobData
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $location,
        public string $url,
        public string $company,
        public string $description = '',
        public string $requirements = '',
        public string $salary = '',
        public string $employmentType = '',
        public string $remoteType = '',
        public array $details = []
    ) {}

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'title' => $this->title,
            'location' => $this->location,
            'url' => $this->url,
            'company' => $this->company,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'salary' => $this->salary,
            'employment_type' => $this->employmentType,
            'remote_type' => $this->remoteType,
            'details' => $this->details,
            'scraped_at' => now()
        ];
    }
}
