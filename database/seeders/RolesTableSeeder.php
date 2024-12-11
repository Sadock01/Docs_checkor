<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
        // Créer des rôles
        // $adminRole = Role::create([
        // 'name' => 'admin', 
        // 'guard_name' => 'web',]);
        // $userRole = Role::create([
        // 'name' => 'user', 
        // 'guard_name' => 'web',]);
    }
}
