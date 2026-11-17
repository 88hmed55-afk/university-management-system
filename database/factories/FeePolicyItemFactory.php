<?php

namespace Database\Factories;

use App\Models\FeeItem;
use App\Models\FeePolicy;
use App\Models\FeePolicyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeePolicyItem>
 */
class FeePolicyItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'fee_policy_id' => FeePolicy::factory(),
            'fee_item_id' => FeeItem::factory(),
            'amount' => '100.00',
        ];
    }
}
