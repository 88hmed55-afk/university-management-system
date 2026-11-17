<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    private static int $sequence = 0;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = 2040 + self::$sequence++;

        return [
            'code' => 'AY-'.$year,
            'name' => 'سنة أكاديمية '.$year,
            'start_date' => $year.'-01-01',
            'end_date' => $year.'-12-31',
        ];
    }
}
