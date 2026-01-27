<?php

namespace Database\Factories;

use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\HealthInsurance;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+30 days');

        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'appointment_status_id' => AppointmentStatus::factory(),
            'health_insurance_id' => HealthInsurance::factory(),
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+1 hour'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
