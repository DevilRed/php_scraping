<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\JobListing;
class JobListingFactory extends Factory
{
    protected $model = JobListing::class;

    public function definition()
    {
        $companies = ['AssureSoft', 'Unosquare', 'JalaSoft'];
        $locations = ['Bolivia', 'Remote', 'Santa Cruz', 'Cochabamba', 'LATAM'];
        $jobTitles = [
            'Software Engineer', 'QA Engineer', 'DevOps Engineer',
            'Frontend Developer', 'Backend Developer', 'Full Stack Developer',
            'Product Manager', 'UI/UX Designer', 'Data Analyst'
        ];

        return [
            'external_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'title' => $this->faker->randomElement($jobTitles),
            'location' => $this->faker->randomElement($locations),
            'url' => $this->faker->url(),
            'company' => $this->faker->randomElement($companies),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'salary' => $this->faker->optional(0.3)->randomElement(['$50k-70k', '$70k-90k', '$90k+', 'Competitive']),
            'employment_type' => $this->faker->randomElement(['Full-time', 'Part-time', 'Contract']),
            'remote_type' => $this->faker->randomElement(['Remote', 'On-site', 'Hybrid']),
            'details' => [
                'method' => $this->faker->randomElement(['selenium', 'static', 'api']),
                'raw_text' => $this->faker->sentence()
            ],
            'scraped_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
