<?php

namespace Database\Factories;

use App\Models\Approval;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => Document::class,
            'subject_id' => Document::factory(),
            'decision' => 'approved',
            'approver_id' => User::factory(),
            'decided_at' => now(),
            'reference' => fake()->unique()->bothify('REF-????'),
        ];
    }
}
