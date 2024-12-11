<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Type;
use Exception;

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
                $query->whereRaw("identifier LIKE " % " . $search . " % "");
            }

            $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'The list of types retrieved',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'items' => $result,
            ]);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error retrieving list types', 'error' => $e->getMessage()], 500);
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

            return response()->json([
                'message' => 'Type created successfully',
                'data' => $type
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error creating type', 
                'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'name' => 'required|string|unique:types,name,' . $id,  // Exclure l'ID actuel pour l'unicité
            'description' => 'nullable|string',
        ]);

        try {

            $type = Type::findOrFail($id);


            $type->name = $validated['name'];
            $type->description = $validated['description'];


            $type->save();


            return response()->json(['message' => 'Type updated successfully', 'data' => $type], 200);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error updating type', 'error' => $e->getMessage()], 500);
        }
    }
}
