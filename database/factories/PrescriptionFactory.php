<?php

namespace Database\Factories;

use App\Enums\PrescriptionType;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory()->doctor(),
            'type' => fake()->randomElement(PrescriptionType::cases()),
            'usage_type' => fake()->optional()->word(),
            'disease_cid' => fake()->optional()->bothify('?##.#'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
