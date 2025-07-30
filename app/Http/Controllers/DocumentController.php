<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\Type;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Verification;
use App\Models\DocumentHistory;
use setasign\Fpdi\Fpdi;

use SimpleSoftwareIO\QrCode\Facades\QrCode;


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
            'hash' => hash('sha256', $request->input('identifier')),
            'type_id' => $request->type_id,
            'beneficiaire' => $request->input('beneficiaire'),
            'date_information' => $request->input('date_information'),
            
        ]);

        $document->users()->attach(Auth::id());

        $document = Document::select(
                'documents.id',
                'documents.identifier',
                'documents.description',
                'documents.type_id',
                'documents.beneficiaire',
                'documents.date_information',
                'documents.informations_complementaires',
                'types.name as type_name'
            )
            ->join('types', 'documents.type_id', '=', 'types.id')
            ->where('documents.id', $document->id)
            ->first();

        return response()->json([
            'status_code' => 200,
            'message' => 'Document créé avec succès !',
            'data' => $document
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur survenue lors de la création du document',
            'error' => $e->getMessage()
        ]);
    }
}

public function storeAutomatic(Request $request)
{
    try {
        $request->validate([
            'file' => 'required|file|mimes:pdf',
            'identifier' => 'required|string|unique:documents,identifier',
        ]);

        // 1. Envoyer le PDF au microservice FastAPI
        $response = Http::attach(
            'file',
            file_get_contents($request->file('file')),
            $request->file('file')->getClientOriginalName()
        )->post('http://127.0.0.1:8001/extract-entities');

        if (!$response->successful()) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Erreur de communication avec le service d’extraction',
                'error' => $response->body()
            ]);
        }

        $data = $response->json();
        $entities = $data['entities'] ?? [];

        // 2. Récupérer les entités
        $beneficiaire = $entities['beneficiaire'][0] ?? null;
        $descriptionParts = $entities['description'] ?? [];
        $description = implode(" ", $descriptionParts);
        $typeLabel = $entities['type_certificat'][0] ?? 'Inconnu';

        // 3. Enregistrer ou retrouver le type automatiquement
       $type = Type::where('name', $typeLabel)->first();

if (!$type) {
    $type = Type::create([
        'name' => $typeLabel,
        'description' => 'Type auto-extrait depuis document',
    ]);
}


        // 4. Créer le document
        $document = Document::create([
            'identifier' => $request->identifier,
            'description' => $description,
            'hash' => hash('sha256', $request->identifier),
            'type_id' => $type->id,
            'beneficiaire' => $beneficiaire,
        ]);

        $document->users()->attach(Auth::id());

        return response()->json([
            'status_code' => 200,
            'message' => 'Document créé automatiquement avec succès',
            'data' => $document->load('type')  // pour inclure le nom du type dans la réponse
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors de la création automatique du document',
            'error' => $e->getMessage()
        ]);
    }
}


public function storeFromExtraction(Request $request)
{
    try {
        // Validation minimale (tu peux l'ajuster selon le frontend)
        $request->validate([
            'identifier' => 'required|string|unique:documents,identifier',
            'description' => 'required|string',
            'type' => 'required|string',
            'beneficiaire' => 'required|string',
            'date_information' => 'nullable|date',
        ]);

        // Rechercher ou créer le type à partir du nom (libellé)
        $type = Type::firstOrCreate(
            ['name' => $request->input('type')],
            ['description' => 'Type auto-créé depuis extraction']
        );

        // Créer le document avec les données extraites
        $document = Document::create([
            'identifier' => $request->input('identifier'),
            'description' => $request->input('description'),
            'hash' => hash('sha256', $request->input('identifier')),
            'type_id' => $type->id,
            'beneficiaire' => $request->input('beneficiaire'),
            'date_information' => $request->input('date_information'),
        ]);

        // Associer l'utilisateur connecté
        $userId = Auth::id();
        $document->users()->attach(Auth::id());
   DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => $userId,
            'old_values' => null,
            'new_values' => json_encode([
                'identifier' => $document->identifier,
                'description' => $document->description,
                'hash' => $document->hash,
                'type_id' => $document->type_id,
                'beneficiaire' => $document->beneficiaire,
                'date_information' => $document->date_information,
            ]),
            'modified_at' => now(),
        ]);
        // Retourner les infos enrichies du document
        $document = Document::select(
                'documents.id',
                'documents.identifier',
                'documents.description',
                'documents.type_id',
                'documents.beneficiaire',
                'documents.date_information',
                'documents.informations_complementaires',
                'types.name as type_name'
            )
            ->join('types', 'documents.type_id', '=', 'types.id')
            ->where('documents.id', $document->id)
            ->first();

        return response()->json([
            'status_code' => 200,
            'message' => 'Document extrait et enregistré avec succès',
            'data' => $document
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors de l’enregistrement du document extrait',
            'error' => $e->getMessage()
        ]);
    }
}

    public function update(DocumentRequest $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $oldValues = $document->toArray();
            $document->update([
                'identifier' => $request->input('identifier'),
                'description' => $request->input('description'),
                'hash' => hash('sha256', $request->input('identifier')), // Mise à jour du hash
                'type_id' => $request->input('type_id'),

            ]);
            DocumentHistory::create([
                'document_id' => $document->id,
                'user_id' => Auth::id(), // Utilisateur qui a effectué la modification
                'old_values' => json_encode($oldValues), // Anciennes valeurs du document
                'new_values' => json_encode($document->toArray()), // Nouvelles valeurs du document
                'modified_at' => now(),
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


   



    

   
  public function showHistory($documentId)
{
    try {
        $history = DocumentHistory::where('document_id', $documentId)
            ->with('user')
            ->orderBy('modified_at', 'asc')
            ->get();

        if ($history->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Aucun historique trouvé pour ce document.'
            ], 404);
        }

        $formattedHistory = [];

        foreach ($history as $entry) {
            $old = json_decode($entry->old_values, true) ?? [];
            $new = json_decode($entry->new_values, true) ?? [];

            $diffs = [];

            // S'il n'y a pas de old_values, c'est la création
            if (empty($old)) {
                $diffs = collect($new)->mapWithKeys(function ($value, $key) {
                    return [$key => ['old' => null, 'new' => $value]];
                })->toArray();

                $formattedHistory[] = [
                    'action' => 'Création',
                    'modified_at' => $entry->modified_at ?? $entry->created_at,
                    'user' => [
                        'firstname' => $entry->user->firstname ?? '',
                        'lastname' => $entry->user->lastname ?? '',
                        'email' => $entry->user->email ?? ''
                    ],
                    'changes' => $diffs
                ];
            } else {
                // Sinon, c’est une modification
                foreach ($new as $key => $newValue) {
                    $oldValue = $old[$key] ?? null;
                    if ($oldValue !== $newValue) {
                        $diffs[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                    }
                }

                $formattedHistory[] = [
                    'action' => 'Modification',
                    'modified_at' => $entry->modified_at ?? $entry->created_at,
                    'user' => [
                        'firstname' => $entry->user->firstname ?? '',
                        'lastname' => $entry->user->lastname ?? '',
                        'email' => $entry->user->email ?? ''
                    ],
                    'changes' => $diffs
                ];
            }
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Historique du document récupéré avec succès.',
            'data' => $formattedHistory
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors de la récupération de l\'historique.',
            'error' => $e->getMessage()
        ]);
    }
}

    public function showAllHistory(Request $request)
    {
        try {
            // Récupérer le paramètre 'page' de la requête, ou par défaut la page 1
            $page = $request->input('page', 1);
            $perPage = 10; // Nombre d'éléments par page
    
            // Récupérer l'historique de tous les documents
            $query = DocumentHistory::with('user')  // Charger l'utilisateur lié à l'historique
                ->orderBy('modified_at', 'desc');  // Tri par date de modification
    
            // Compter le nombre total d'historiques
            $total = $query->count();
    
            // Pagination : appliquer l'offset et la limite
            $history = $query->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();
    
            // Formater les données récupérées
            $formattedHistory = $history->map(function ($item) {
                // Convertir old_values et new_values en tableaux associatifs
                $oldValues = json_decode($item->old_values, true);
                $newValues = json_decode($item->new_values, true);
    
                // Trouver les différences entre old_values et new_values
                $changes = [];
                foreach ($oldValues as $key => $oldValue) {
                    // Vérifier si la valeur a changé
                    if (isset($newValues[$key]) && $oldValue != $newValues[$key]) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newValues[$key]
                        ];
                    }
                }
    
                // Retourner un format avec les changements et les infos utilisateur
                return [
                    'id' => $item->id,
                    'document_id' => $item->document_id,
                    'user_id' => $item->user_id,
                    'modified_at' => $item->modified_at,
                    'changes' => $changes,
                    'user' => [
                        'id' => $item->user->id,
                        'firstname' => $item->user->firstname,
                        'lastname' => $item->user->lastname,
                        'email' => $item->user->email,
                    ],
                ];
            });
    
            // Retourner les données avec les informations de pagination
            return response()->json([
                'status_code' => 200,
                'message' => 'Historique des documents récupéré avec succès.',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'data' => $formattedHistory,
            ], 200);
    
        } catch (Exception $e) {
            // Gérer les erreurs
            return response()->json([
                'status_code' => 500,
                'message' => 'Erreur lors de la récupération de l\'historique.',
                'error' => $e->getMessage()
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
            'status_code' => 200,
            'data' => $statistics,
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
