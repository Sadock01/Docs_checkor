<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Type;
use Exception;
use Illuminate\Support\Facades\Auth;


class TypeController extends Controller
{

    public function index(Request $request)
    {

        try {

            $query = Type::query();
            $perPage = 5;
            $page = $request->input('page', 5);
            $search = $request->input('search');


            if ($search) {
                $query->whereRaw("identifier LIKE ?", ['%' . $search . '%']);
            }

            $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Types recupérés avec succès',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'data' => $result,
            ]);
        } catch (Exception $e) {

            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur survenue lors de la recupération des Types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
      
        $validated = $request->validate([
            'name' => 'required|string|unique:types',
            'description' => 'nullable|string',
        ]);

        try {
           
            $type = Type::create([
                'name' => $validated['name'],
                'description' => $validated['description'],

            ]);
           
            $type->users()->attach(Auth::id());
             
            return response()->json([
                'message' => 'Type créé avec succès',
                'data' => $type
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur lors de la création du Type',
                'error' => $e->getMessage()
            ], );
        }
    }

    public function update(Request $request, $id)
{
    try {
        // Étape 1 : Validation des données d'entrée
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:types,name,' . $id, // Exclure l'ID actuel pour l'unicité
            'description' => 'nullable|string',
        ]);

        // Étape 2 : Récupération du type à mettre à jour
        $type = Type::findOrFail($id);

        // Étape 3 : Mise à jour des données
        $type->update([
            'name' => $validated['name'] ?? $type->name,
            'description' => $validated['description'] ?? $type->description,
        ]);

        // Étape 4 : Réponse en cas de succès
        return response()->json([
            'status_code' => 200,
            'message' => 'Le type a été mis à jour avec succès.',
            'data' => $type,
        ]);
    } catch (Exception $e) {
        // Étape 5 : Gestion des erreurs
        return response()->json([
            'status_code' => 500,
            'message' => 'Une erreur est survenue lors de la mise à jour du type.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
