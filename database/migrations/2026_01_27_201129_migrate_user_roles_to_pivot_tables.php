<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // Seed roles
        $medicoId = DB::table('roles')->insertGetId([
            'name' => 'medico', 'label' => 'Médico', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $secretarioId = DB::table('roles')->insertGetId([
            'name' => 'secretario', 'label' => 'Secretário', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $tecnicoId = DB::table('roles')->insertGetId([
            'name' => 'tecnico', 'label' => 'Técnico', 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Seed permissions
        $permissionNames = [
            'manage-medical-records' => 'Gerenciar Prontuários',
            'manage-prescriptions' => 'Gerenciar Receituários',
            'manage-prescription-templates' => 'Gerenciar Modelos de Receituário',
            'manage-certificate-templates' => 'Gerenciar Modelos de Atestado',
            'manage-roles' => 'Gerenciar Cargos',
        ];

        $permissionIds = [];
        foreach ($permissionNames as $name => $label) {
            $permissionIds[$name] = DB::table('permissions')->insertGetId([
                'name' => $name, 'label' => $label, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Assign all permissions to medico role
        foreach ($permissionIds as $permId) {
            DB::table('permission_role')->insert([
                'permission_id' => $permId, 'role_id' => $medicoId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Map existing role column values to role IDs
        $roleMap = [
            'Medico' => $medicoId,
            'Secretario' => $secretarioId,
            'Tecnico' => $tecnicoId,
        ];

        // Migrate existing users
        $users = DB::table('users')->whereNotNull('role')->get();
        foreach ($users as $user) {
            if (isset($roleMap[$user->role])) {
                DB::table('role_user')->insert([
                    'role_id' => $roleMap[$user->role],
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Drop the role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Secretario')->after('email');
        });
    }
};
