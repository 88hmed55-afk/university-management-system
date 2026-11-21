<?php

namespace Database\Factories;

use App\Models\PostgraduateRecord;
use App\Models\ResearchProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchProposal>
 */
class ResearchProposalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'proposal_no' => fake()->unique()->bothify('PROP-####'),
            'postgraduate_record_id' => PostgraduateRecord::factory(),
            'version_no' => 1,
            'title' => fake()->bothify('عنوان بحث ???'),
            'abstract' => fake()->paragraph(),
            'status' => 'draft',
        ];
    }
}
