<?php

namespace App\Http\Controllers;


use App\Models\Document;
use App\Http\Requests\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Verification;

use Exception;

class DocumentController extends Controller
{
    public function index(Request $request)
    {

        try {

            $query = Document::select(
                'documents.id',
                'documents.identifier',
                'documents.description',
                'documents.type_id',
                'types.name as type_name' // Récupérer le nom du type
            )
            ->join('types', 'documents.type_id', '=', 'types.id') // Jointure avec types
            ->orderBy('documents.created_at', 'desc');
            $perPage = 10;
            $page = $request->input('page', 1);
            $search = $request->input('search');


            if ($search) {
                $query->whereRaw("identifier LIKE ?", ['%' . $search . '%']);
            }
            // $query->orderBy('created_at', 'desc');
            $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'message' => 'Les documents ont été récupérés avec succès',
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

    public function show($id)
{
    try {
        $document = Document::select(
            'documents.id',
            'documents.identifier',
            'documents.description',
            'documents.type_id',
            'types.name as type_name' // Récupérer le nom du type
        )
        ->join('types', 'documents.type_id', '=', 'types.id') // Jointure avec types
        ->where('documents.id', $id)
        ->firstOrFail();

        return response()->json([
            'status_code' => 200,
            'message' => 'Document récupéré avec succès.',
            'data' => $document,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'statut_code' => 404,
            'message' => 'Document introuvable.',
            'error' => $e->getMessage()
        ], 404);
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
            
         $document = Document::select(
            'documents.id',
            'documents.identifier',
            'documents.description',
            'documents.type_id',
            'types.name as type_name'
        )
        ->join('types', 'documents.type_id', '=', 'types.id')
        ->where('documents.id', $document->id)
        ->first();
        
            return response()->json([
                'status_code' => 200,
                'message' => 'Document creé avec succès!',
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
                'message' => 'Document mise à jour avec succès.',
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
        // La vérification réussit (success: true)
        $status = 'Authentique'; // Statut basé sur le succès de la vérification

        // Enregistrer la vérification dans la table `verifications`
        $verification = Verification::create([
            'identifier' => $document->identifier,
            'verification_date' => now(), // Date et heure actuelles
            'status' => $status, // Statut basé sur le succès de la vérification
        ]);

        // Retourner la description du document et le statut de la vérification
        return response()->json([
            'success' => true,
            'data' => [
                'description' => $document->description,
                'status' => $verification->status,
            ],
        ], 200);
    } else {
        // La vérification échoue (success: false)
        $status = 'Frauduleux'; // Statut basé sur l'échec de la vérification

        // Enregistrer la vérification dans la table `verifications`
        $verification = Verification::create([
           'identifier' => $identifier,  // Aucun document associé
            'verification_date' => now(), // Date et heure actuelles
            'status' => $status, // Statut basé sur l'échec de la vérification
        ]);

        // Retourner un message d'erreur si le document n'existe pas
        return response()->json([
            'success' => false,
            'message' => 'Le document avec cet identifiant n\'existe pas.',
            'data' => [
                'status' => $verification->status,
            ],
        ], 404);
    }
}

    public function getVerificationHistory(Request $request)
{
    try {
        $query = Verification::query();
        $perPage = 10;
        $page = $request->input('page', 1);
        $search = $request->input('search');

        // Filtrer par identifiant du document si une recherche est effectuée
        if ($search) {
            $query->whereHas('document', function ($q) use ($search) {
                $q->where('identifier', 'LIKE', '%' . $search . '%');
            });
        }

        // Trier par date de vérification
        $query->orderBy('verification_date', 'desc');

        // Pagination
        $total = $query->count();
        $result = $query->with('document') // Charger les données du document associé
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'status_code' => 200,
            'message' => 'Historique des vérifications récupéré avec succès.',
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'data' => $result,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors de la récupération de l\'historique des vérifications.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getVerificationsByStatus(Request $request)
{
    try {
        $status = $request->input('status'); // Récupérer le paramètre 'status'
        $perPage = 10;
        $page = $request->input('page', 1);

        if (!$status) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Le paramètre "status" est requis.',
            ], 400);
        }

        // Filtrer les vérifications par status
        $query = Verification::where('status', $status);

        // Pagination
        $total = $query->count();
        $result = $query->with('document') // Charger les données du document associé
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'status_code' => 200,
            'message' => 'Historique des vérifications filtré par statut récupéré avec succès.',
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'data' => $result,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors de la récupération des vérifications par statut.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function statisticsLastDays($days)
{
    $statistics = Verification::selectRaw('DATE(verification_date) as date, COUNT(*) as total')
    ->where('verification_date', '>=', now()->subDays($days))
    ->groupBy('date')
    ->orderBy('date', 'ASC')
    ->get();

    return response()->json([
        'success' => true,
        'status_code'=> 200,
        'data' => $statistics,
    ]);
}

public function totalVerifications()
{
    $total = Verification::count();

    return response()->json([
        'success' => true,
        'total_verifications' => $total,
    ]);
}

public function totalDocuments()
{
    $total = Document::count();

    return response()->json([
        'success' => true,
        'total_documents' => $total,
    ]);
}


}
