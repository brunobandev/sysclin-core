<?php

namespace Database\Seeders;

use App\Models\AppointmentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppointmentStatus::insert([
            ['name' => 'Marcada', 'created_at' => now()],
            ['name' => 'Confirmada', 'created_at' => now()],
            ['name' => 'Aguardando na clínica', 'created_at' => now()],
            ['name' => 'Realizada', 'created_at' => now()],
            ['name' => 'Cancelada por telefone', 'created_at' => now()],
            ['name' => 'Em atendimento', 'created_at' => now()],
            ['name' => 'Ausente', 'created_at' => now()],
            ['name' => 'Cancelada pelo cliente por Whats (sistema)', 'created_at' => now()],
            ['name' => 'Confirmada pelo Whats (sistema)', 'created_at' => now()],
        ]);
    }
}
