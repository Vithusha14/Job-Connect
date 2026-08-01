<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    public const CREATED_AT = 'applied_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'job_id', 'candidate_id', 'cover_letter', 'status',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
