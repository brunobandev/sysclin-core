<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
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
            'reason' => fake()->sentence(),
            'disease_cid' => fake()->bothify('?##.#'),
            'subjective' => fake()->paragraph(),
            'objective' => fake()->paragraph(),
            'exams' => fake()->paragraph(),
            'impression' => fake()->paragraph(),
            'conduct' => fake()->paragraph(),
            'description' => fake()->paragraph(),
        ];
    }
}
