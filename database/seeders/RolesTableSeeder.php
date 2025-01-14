<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // Assurez-vous que Spatie est installé
use Spatie\Permission\Models\Permission;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roles = [
            ['name' => 'admin'], // Rôle pour les administrateurs
            ['name' => 'collaborateur'], // Rôle pour les collaborateurs
        ];

        $permissions = [
            'create-document',
            'edit-document',
            'view-document',
            'create-type',
            'edit-type',
            'view-type',
            'delete-type',
            'create-user',
            'edit-user',
            'view-user',
        ];

        // Boucle pour insérer chaque rôle
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']]); // Évite les doublons
        }
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $collaborateurRole = Role::firstOrCreate(['name' => 'collaborateur']);

        // Assigner toutes les permissions à l'administrateur
        $adminRole->syncPermissions($permissions);

        $collaborateurRole->syncPermissions([
            'create-document',
            'edit-document',
            'view-document',
            'create-type',
            'edit-type',
            'view-type',
        ]);

    }
}
