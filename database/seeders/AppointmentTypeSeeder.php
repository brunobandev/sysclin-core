<?php

namespace Database\Seeders;

use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppointmentType::insert([
            ['name' => 'Consulta', 'created_at' => now()],
            ['name' => 'Retorno', 'created_at' => now()],
            ['name' => 'Exame', 'created_at' => now()],
            ['name' => 'Vacina', 'created_at' => now()],
            ['name' => 'Cirurgia', 'created_at' => now()],
        ]);
    }
}
