<?php

namespace Database\Factories;

use App\Models\BibliographicRecord;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reservation_no' => fake()->unique()->bothify('RES-####'),
            'bibliographic_record_id' => BibliographicRecord::factory(),
            'beneficiary_type' => Student::class,
            'beneficiary_id' => Student::factory(),
            'status' => 'requested',
            'acted_by' => User::factory(),
        ];
    }
}
