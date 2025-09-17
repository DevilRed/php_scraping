<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        'status',
        'jobs_found',
        'jobs_saved',
        'filters_applied',
        'scraping_method',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
        'duration_seconds'
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function jobListings()
    {
        return $this->hasMany(JobListing::class, 'company', 'company')
            ->where('created_at', '>=', $this->started_at)
            ->where('created_at', '<=', $this->completed_at ?? now());
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }
}
