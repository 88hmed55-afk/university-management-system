<?php

namespace Database\Factories;

use App\Models\AdmissionReview;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdmissionReview>
 */
class AdmissionReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
        ];
    }
}
