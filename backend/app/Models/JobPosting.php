<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'employer_id', 'category_id', 'title', 'description', 'requirements',
        'salary_min', 'salary_max', 'location', 'job_type', 'deadline',
        'status', 'vacancies',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class, 'employer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }
}
