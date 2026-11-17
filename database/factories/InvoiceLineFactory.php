<?php

namespace Database\Factories;

use App\Models\FeeItem;
use App\Models\InvoiceLine;
use App\Models\StudentInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_invoice_id' => StudentInvoice::factory(),
            'fee_item_id' => FeeItem::factory(),
            'amount' => '100.00',
        ];
    }
}
