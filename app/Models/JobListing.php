<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'title',
        'location',
        'url',
        'company',
        'description',
        'requirements',
        'salary',
        'employment_type',
        'remote_type',
        'details',
        'scraped_at'
    ];

    protected $casts = [
        'details' => 'array',
        'scraped_at' => 'datetime'
    ];

    public function scopeByCompany($query, string $company)
    {
        return $query->where('company', $company);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('scraped_at', '>=', Carbon::now()->subHours($hours));
    }
}
