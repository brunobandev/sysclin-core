<?php

namespace Database\Factories;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'medication' => fake()->word(),
            'quantity' => fake()->randomDigitNotNull().' comprimidos',
            'frequency' => fake()->randomElement(['8/8h', '12/12h', '6/6h', '1x ao dia']),
            'usage_type' => fake()->optional()->randomElement(['Oral', 'Tópico', 'Injetável']),
        ];
    }
}
