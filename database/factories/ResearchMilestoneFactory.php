<?php

namespace Database\Factories;

use App\Models\PostgraduateRecord;
use App\Models\ResearchMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchMilestone>
 */
class ResearchMilestoneFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'milestone_no' => fake()->unique()->bothify('MIL-####'),
            'postgraduate_record_id' => PostgraduateRecord::factory(),
            'title' => fake()->bothify('مرحلة ???'),
            'status' => 'planned',
        ];
    }
}
