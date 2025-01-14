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
                'items' => $result,
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
        dd('type');
        // Validation des données d'entrée
        $validated = $request->validate([
            'name' => 'required|string|unique:types,name,' . $id,  // Exclure l'ID actuel pour l'unicité
            'description' => 'nullable|string',
        ]);
dd('type');
        try {

            $type = Type::findOrFail($id);


            $type->name = $validated['name'];
            $type->description = $validated['description'];


            $type->save();


            return response()->json([
                'statut_code' => 200,
                'message' => 'Type a été mise à jour avec succès',
                'data' => $type
            ], );
        } catch (Exception $e) {

            return response()->json([
                'satut_code' => 401,
                'message' => 'Erreur survenue lors de la mise à jour du Type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
