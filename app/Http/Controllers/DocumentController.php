<?php

namespace App\Http\Controllers;


use App\Models\Document;
use App\Http\Requests\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Exception;

class DocumentController extends Controller
{
    public function index(Request $request)
    {

        try {

            $query = Document::query();
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
                'status_message' => 'Les documents ont été récupérés avec succès',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'data' => $result,
            ]);
        } catch (Exception $e) {

            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur survenue lors de la recuperation des documents',
                'error' => $e->getMessage()
            ], );
        }


    }

    public function store(DocumentRequest $request)
    {
        
        try {
            $document = Document::create([
                'identifier' => $request->input('identifier'),
                'description' => $request->input('description'),
                'hash' => hash('sha256', $request->input('identifier')), // Génération automatique du hash
                'type_id' => $request->type_id,

            ]);
         $document->users()->attach(Auth::id());
            
            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document creé avec succès.',
                'data' => $document
            ]);
        } catch (Exception $e) {

            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur survenue lors de la création du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(DocumentRequest $request, $id)
    {
        try {
            $document = Document::findOrFail($id);

            $document->update([
                'identifier' => $request->input('identifier'),
                'description' => $request->input('description'),
                'hash' => hash('sha256', $request->input('identifier')), // Mise à jour du hash
                'type_id' => $request->input('type_id'),
               
            ]);
            $document->users()->attach(Auth::id());
            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document mise à jour avec succès.',
                'data' => $document,
            ]);
        } catch (Exception $e) {

            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur survenue lors de la mise à jour du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Document $document)
    {
        try {


            $document->delete();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document supprimé avec succès',
                'data' => $document,

            ]);
        } catch (Exception $e) {

            return response()->json([
                'statut_code' => 401,
                'message' => 'Erreur survenue lors de la suppression du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function verifyDocument(Request $request)
    {
        // Valider les données envoyées par le front
        $request->validate([
            'identifier' => 'required|string',
        ]);

        // Récupérer l'identifiant envoyé
        $identifier = $request->input('identifier');

        // Vérifier si le document existe dans la base de données
        $document = Document::where('identifier', $identifier)->first();

        if ($document) {
            // Retourner la description du document
            return response()->json([
                'success' => true,
                'data' => $document->description,
            ], 200);
        } else {
            // Retourner un message d'erreur si le document n'existe pas
            return response()->json([
                'success' => false,
                'message' => 'Le document avec cet identifiant n\'existe pas.',
            ], 404);
        }
    }
}
