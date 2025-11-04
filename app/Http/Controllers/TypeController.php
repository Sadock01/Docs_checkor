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
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:types,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $type = Type::findOrFail($id);

        // Vérifier s'il est déjà utilisé par des documents
        if ($type->documents()->exists()) {
            return response()->json([
                'status_code' => 403,
                'message' => 'Ce type est déjà utilisé par un document et ne peut pas être modifié.',
            ], 403);
        }

        $type->update([
            'name' => $validated['name'] ?? $type->name,
            'description' => $validated['description'] ?? $type->description,
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'Le type a été mis à jour avec succès.',
            'data' => $type,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Une erreur est survenue lors de la mise à jour du type.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function destroy($id)
{
    try {
        $type = Type::findOrFail($id);

        // Compter le nombre de documents qui utilisent ce type
        $nombreDocuments = $type->documents()->count();

        // Si le type est utilisé par des documents, empêcher la suppression
        if ($nombreDocuments > 0) {
            return response()->json([
                'status_code' => 409, // Conflict
                'message' => "Impossible de supprimer ce type. Il est rattaché à {$nombreDocuments} document(s).",
                'nombre_documents' => $nombreDocuments,
            ], 409);
        }

        // Soft delete
        $type->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Le type a été supprimé (soft delete) avec succès.',
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Une erreur est survenue lors de la suppression du type.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
