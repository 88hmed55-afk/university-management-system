<?php

namespace App\Models;

use Database\Factories\AdmissionReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionReview extends Model
{
    /** @use HasFactory<AdmissionReviewFactory> */
    use HasFactory;

    protected $fillable = ['application_id', 'reviewer_id', 'outcome', 'notes'];

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
