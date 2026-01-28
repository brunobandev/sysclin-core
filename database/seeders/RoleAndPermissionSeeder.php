<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medico = Role::firstOrCreate(['name' => 'medico'], ['label' => 'Médico']);
        Role::firstOrCreate(['name' => 'secretario'], ['label' => 'Secretário']);
        Role::firstOrCreate(['name' => 'tecnico'], ['label' => 'Técnico']);

        $permissions = [
            'manage-medical-records' => 'Gerenciar Prontuários',
            'manage-prescriptions' => 'Gerenciar Receituários',
            'manage-prescription-templates' => 'Gerenciar Modelos de Receituário',
            'manage-certificate-templates' => 'Gerenciar Modelos de Atestado',
            'manage-roles' => 'Gerenciar Cargos',
            'start-consultation' => 'Iniciar Consulta',
        ];

        foreach ($permissions as $name => $label) {
            $permission = Permission::firstOrCreate(['name' => $name], ['label' => $label]);
            $medico->permissions()->syncWithoutDetaching($permission);
        }
    }
}
