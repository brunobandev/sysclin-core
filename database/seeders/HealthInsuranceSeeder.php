<?php

namespace Database\Seeders;

use App\Models\HealthInsurance;
use Illuminate\Database\Seeder;

class HealthInsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HealthInsurance::insert([
            ['name' => 'Particular', 'created_at' => now()],
        ]);
    }
}
