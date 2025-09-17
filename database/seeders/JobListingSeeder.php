<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobListing;
use App\Models\ScrapingLog;

class JobListingSeeder extends Seeder
{
    public function run()
    {
        // Create sample job listings
        JobListing::factory(50)->create();

        // Create sample scraping logs
        $companies = ['AssureSoft', 'Unosquare', 'JalaSoft'];

        foreach ($companies as $company) {
            for ($i = 0; $i < 5; $i++) {
                ScrapingLog::create([
                    'company' => $company,
                    'status' => fake()->randomElement(['success', 'success', 'success', 'failure']), // 75% success rate
                    'jobs_found' => fake()->numberBetween(5, 20),
                    'jobs_saved' => fake()->numberBetween(3, 18),
                    'scraping_method' => fake()->randomElement(['selenium', 'static', 'api']),
                    'started_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    'duration_seconds' => fake()->numberBetween(10, 120),
                ]);
            }
        }
    }
}
