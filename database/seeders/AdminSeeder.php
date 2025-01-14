<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // N'oubliez pas d'importer le modèle User
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Créer un utilisateur administrateur
        User::create([
            'firstname' => 'john',   // Nom de l'administrateur
            'lastname' => 'Doe',
            'email' => 'johndoe@mail.test', // Email de l'administrateur
            'password' => Hash::make('password'), // Mot de passe sécurisé
            'status' => true, // Activer le compte
            'role_id' => 1, // Attribuer un rôle d'admin (par exemple role_id = 1, à ajuster en fonction de vos rôles)
            
        ]);
    }
}


