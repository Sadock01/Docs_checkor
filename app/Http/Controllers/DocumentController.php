<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\ActivitiesLog;
use App\Models\Type;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Verification;
use App\Models\DocumentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
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
                'documents.beneficiaire',
                'documents.date_information',
                'documents.informations_complementaires',
                'documents.type_id',
                'types.name as type_name' // Récupérer le nom du type
            )
                ->join('types', 'documents.type_id', '=', 'types.id') // Jointure avec types
                ->orderBy('documents.created_at', 'desc');
            $perPage = 3;
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
                'documents.beneficiaire',
                'documents.date_information',
                'documents.informations_complementaires',
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
                'message' => 'Document introuvable.'.$e->getMessage(),
            'error' => $e->getMessage()
        ], 404);
    }
}

// public function store(Request $request)
// {
//     $results = [
//         "success" => [],
//         "failed" => []
//     ];

//     $documents = $request->input('documents', []);

//     foreach ((array) $documents as $docData) {
//         $identifier = $docData['identifier'] ?? null;

//         if (!$identifier) {
//             $results['failed'][] = [
//                 "identifier" => "inconnu",
//                 "message" => "Identifiant manquant."
//             ];
//             continue;
//         }

//         try {
//             DB::beginTransaction();

//             // Vérifier doublon rapide
//             if (Document::where('identifier', $identifier)->exists()) {
//                 $msg = "Doublon détecté : identifiant déjà utilisé.";
//                 $results['failed'][] = [
//                     "identifier" => $identifier,
//                     "message" => $msg
//                 ];

//                 $user = Auth::user();
//                 ActivitiesLog::create([
//                     "identifier" => $identifier,
//                     "status" => "failed",
//                     "message" => $msg,
//                     "user_id" => $user ? $user->id : null,
//                     "firstname" => $user ? $user->firstname : null,
//                     "lastname" => $user ? $user->lastname : null,
//                 ]);

//                 DB::rollBack();
//                 continue;
//             }

//             // 1️⃣ type
//             $typeName = $docData['type_name'] ?? 'Autre';
//             $type = Type::firstOrCreate(
//                 ['name' => $typeName],
//                 ['name' => $typeName]
//             );

//             // 2️⃣ création
//             $document = Document::create([
//                 'identifier' => $identifier,
//                 'description' => $docData['description'] ?? null,
//                 'hash' => hash('sha256', $identifier),
//                 'type_id' => $type->id,
//                 'beneficiaire' => $docData['beneficiaire'] ?? null,
//                 'date_information' => $docData['date_information'] ?? null,
//             ]);

//             $user = Auth::user();
//             if ($user) {
//                 $document->users()->attach($user->id);
//             }

//             $results['success'][] = [
//                 "identifier" => $identifier,
//                 "message" => "Document enregistré avec succès."
//             ];

//             ActivitiesLog::create([
//                 "identifier" => $identifier,
//                 "status" => "success",
//                 "message" => "Document enregistré avec succès.",
//                 "user_id" => $user ? $user->id : null,
//                 "firstname" => $user ? $user->firstname : null,
//                 "lastname" => $user ? $user->lastname : null,
//             ]);

//             DB::commit();

//         } catch (QueryException $qe) {
//             DB::rollBack();
//             $msg = 'Erreur base de données : ' . $qe->getMessage();
//             $results['failed'][] = [
//                 "identifier" => $identifier,
//                 "message" => $msg
//             ];
//             $user = Auth::user();
//             DocumentHistory::create([
//                 "identifier" => $identifier,
//                 "status" => "failed",
//                 "message" => $msg,
//                 "user_id" => $user ? $user->id : null,
//                 "firstname" => $user ? $user->firstname : null,
//                 "lastname" => $user ? $user->lastname : null,
//             ]);
//         } catch (Exception $e) {
//             DB::rollBack();
//             $msg = 'Erreur : ' . $e->getMessage();
//             $results['failed'][] = [
//                 "identifier" => $identifier,
//                 "message" => $msg
//             ];
//             $user = Auth::user();
//             DocumentHistory::create([
//                 "identifier" => $identifier,
//                 "status" => "failed",
//                 "message" => $msg,
//                 "user_id" => $user ? $user->id : null,
//                 "firstname" => $user ? $user->firstname : null,
//                 "lastname" => $user ? $user->lastname : null,
//             ]);
//         }
//     }

//     return response()->json($results);
// }




public function store(Request $request)
{
    // Initialisation des compteurs et des erreurs uniquement
    $reussi = 0;
    $echoue = 0;
    $erreurs = [];
    $identifiers_reussis = []; // Pour stocker les identifiants réussis pour le fichier Excel

    $documents = $request->input('documents', []);

    foreach ((array) $documents as $docData) {
        $identifier = $docData['identifier'] ?? null;

        if (!$identifier) {
            $erreurs[] = [
                "identifier" => "inconnu",
                "erreur" => "Identifiant manquant."
            ];
            $echoue++;
            continue;
        }

        try {
            DB::beginTransaction();

            // Vérifier doublon rapide
            if (Document::where('identifier', $identifier)->exists()) {
                $msg = "Doublon détecté : identifiant déjà utilisé.";
                $erreurs[] = [
                    "identifier" => $identifier,
                    "erreur" => $msg
                ];
                $echoue++;

                $user = Auth::user();
                ActivitiesLog::create([
                    "identifier" => $identifier,
                    "status" => "failed",
                    "message" => $msg,
                    "user_id" => $user ? $user->id : null,
                    "firstname" => $user ? $user->firstname : null,
                    "lastname" => $user ? $user->lastname : null,
                ]);

                DB::rollBack();
                continue;
            }

            // 1️⃣ type
            $typeName = $docData['type_name'] ?? 'Autre';
            $type = Type::firstOrCreate(
                ['name' => $typeName],
                ['name' => $typeName]
            );

            // 2️⃣ création
            $document = Document::create([
                'identifier' => $identifier,
                'description' => $docData['description'] ?? null,
                'hash' => hash('sha256', $identifier),
                'type_id' => $type->id,
                'beneficiaire' => $docData['beneficiaire'] ?? null,
                'date_information' => !empty($docData['date_information'])
                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $docData['date_information'])->format('Y-m-d')
                    : null,
            ]);

            if (!$type->is_used) {
                $type->update(['is_used' => true]);
            }

            $user = Auth::user();
            if ($user) {
                $document->users()->attach($user->id);
            }

            // Succès : on incrémente et on stocke l'identifiant pour le fichier Excel
            $reussi++;
            $identifiers_reussis[] = $identifier;

            ActivitiesLog::create([
                "identifier" => $identifier,
                "status" => "success",
                "message" => "Document enregistré avec succès.",
                "user_id" => $user ? $user->id : null,
                "firstname" => $user ? $user->firstname : null,
                "lastname" => $user ? $user->lastname : null,
            ]);

            DB::commit();

        } catch (QueryException $qe) {
            DB::rollBack();
            $msg = 'Erreur base de données : ' . $qe->getMessage();
            $erreurs[] = [
                "identifier" => $identifier,
                "erreur" => $msg
            ];
            $echoue++;
            
            $user = Auth::user();
            DocumentHistory::create([
                "identifier" => $identifier,
                "status" => "failed",
                "message" => $msg,
                "user_id" => $user ? $user->id : null,
                "firstname" => $user ? $user->firstname : null,
                "lastname" => $user ? $user->lastname : null,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            $msg = 'Erreur : ' . $e->getMessage();
            $erreurs[] = [
                "identifier" => $identifier,
                "erreur" => $msg
            ];
            $echoue++;
            
            $user = Auth::user();
            DocumentHistory::create([
                "identifier" => $identifier,
                "status" => "failed",
                "message" => $msg,
                "user_id" => $user ? $user->id : null,
                "firstname" => $user ? $user->firstname : null,
                "lastname" => $user ? $user->lastname : null,
            ]);
        }
    }

    // Génération du fichier CSV avec le récapitulatif si plus de 2 documents traités
    $csvUrl = null;
    if (count($documents) >= 2) {
        try {
            // Dossier où seront stockés les fichiers CSV
            $recapDir = storage_path('app/public/recaps');

            // Création du dossier s'il n'existe pas
            if (!file_exists($recapDir)) {
                mkdir($recapDir, 0777, true);
            }

            // Nom du fichier CSV
            $filename = 'documents_recap_' . time() . '.csv';
            $path = $recapDir . '/' . $filename;

            // Ouvrir le fichier pour écriture
            $handle = fopen($path, 'w');
            // BOM UTF-8 pour Excel
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // En-tête CSV
            fputcsv($handle, ['Identifier', 'Status', 'Message/Erreur'], ';');

            // Ajouter les succès avec leurs identifiants réels
            foreach ($identifiers_reussis as $identifier_reussi) {
                fputcsv($handle, [$identifier_reussi, 'success', 'Document créé avec succès'], ';');
            }

            // Ajouter les erreurs détaillées
            foreach ($erreurs as $erreur) {
                fputcsv($handle, [$erreur['identifier'], 'failed', $erreur['erreur']], ';');
            }

            // Fermeture du fichier
            fclose($handle);

            // URL à renvoyer au front pour téléchargement
            $csvUrl = asset('storage/recaps/' . $filename);
        } catch (\Exception $e) {
            \Log::error('Erreur génération CSV : ' . $e->getMessage());
            // On continue même si la génération CSV échoue
        }
    }

    // Retourner le récapitulatif optimisé
    return response()->json([
        'status_code' => 200,
        'recap' => [
            'total_reussi' => $reussi,
            'total_echoue' => $echoue,
            'total_traite' => $reussi + $echoue,
        ],
        // Retourner seulement les erreurs si il y en a
        'erreurs' => !empty($erreurs) ? $erreurs : null,
        'csv_recap' => $csvUrl // null si pas généré
    ], 200);
}






public function create(Request $request)
{
    try {
        // 1️⃣ On récupère ou crée le type à partir du nom
        $type = Type::firstOrCreate(
            ['name' => $request->input('type_name')],
            ['name' => $request->input('type_name')]
        );

        // 2️⃣ Création du document
        $document = Document::create([
            'identifier' => $request->input('identifier'),
            'description' => $request->input('description'),
            'hash' => hash('sha256', $request->input('identifier')),
            'type_id' => $type->id, // ✅ on utilise l'ID du type créé/trouvé
            'beneficiaire' => $request->input('beneficiaire'),
            'date_information' => $request->input('date_information'),
        ]);

        // Associer le document à l’utilisateur connecté
        $document->users()->attach(Auth::id());

        // 3️⃣ Récupérer avec infos type
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




    public function update(DocumentRequest $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $oldValues = $document->toArray();
            $document->update([
                'identifier' => $request->input('identifier'),
                'description' => $request->input('description'),
                'beneficiaire' => $request->input('beneficiaire'),
                 'date_information' => $request->input('date_information'),
                'hash' => hash('sha256', $request->input('identifier')), // Mise à jour du hash
                'type_id' => $request->input('type_id'),
               
            ]);

             $changes = $document->getChanges();
            DocumentHistory::create([
                'document_id' => $document->id,
                'user_id' => Auth::id(), // Utilisateur qui a effectué la modification
                'old_values' => json_encode(array_intersect_key($oldValues, $changes)),
                'new_values' => json_encode($changes), // Nouvelles valeurs du document
                 'changed_fields'=> json_encode(array_keys($changes)), 
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
        $document->delete(); // soft delete

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



   

public function filter(Request $request)
{
    try {
        $query = Document::select(
            'documents.id',
            'documents.identifier',
            'documents.description',
            'documents.beneficiaire',
            'documents.date_information',
            'documents.type_id',
            'documents.created_at',
            'types.name as type_name'
        )
        ->join('types', 'documents.type_id', '=', 'types.id')
        ->orderBy('documents.created_at', 'desc');

        // 🔎 1. Recherche spécifique par identifiant
        if ($request->filled('identifier')) {
            $identifier = $request->input('identifier');
            $query->where('documents.identifier', 'LIKE', "%{$identifier}%");
        }

        // 🏷️ 2. Filtre par libellé/nom du type
        if ($request->filled('type_name')) {
            $typeName = $request->input('type_name');
            $query->where('types.name', 'LIKE', "%{$typeName}%");
        }

        // 🔖 3. Filtre par type_id (alternative au nom)
        if ($request->filled('type_id')) {
            $query->where('documents.type_id', $request->input('type_id'));
        }

        // 📅 4. Filtre par période de date_information (date du document)
        if ($request->filled('date_information_start') && $request->filled('date_information_end')) {
            $startDate = $request->input('date_information_start');
            $endDate = $request->input('date_information_end');
            $query->whereBetween('documents.date_information', [$startDate, $endDate]);
        } elseif ($request->filled('date_information_start')) {
            // Si seulement la date de début est fournie
            $startDate = $request->input('date_information_start');
            $query->where('documents.date_information', '>=', $startDate);
        } elseif ($request->filled('date_information_end')) {
            // Si seulement la date de fin est fournie
            $endDate = $request->input('date_information_end');
            $query->where('documents.date_information', '<=', $endDate);
        }

        // 📆 5. Filtre par période de création (created_at) - documents créés entre ces dates
        if ($request->filled('created_start') && $request->filled('created_end')) {
            $createdStart = $request->input('created_start');
            $createdEnd = $request->input('created_end');
            // Inclure toute la journée pour la date de fin
            $createdEnd = date('Y-m-d 23:59:59', strtotime($createdEnd));
            $query->whereBetween('documents.created_at', [$createdStart, $createdEnd]);
        } elseif ($request->filled('created_start')) {
            // Si seulement la date de début est fournie
            $createdStart = $request->input('created_start');
            $query->where('documents.created_at', '>=', $createdStart);
        } elseif ($request->filled('created_end')) {
            // Si seulement la date de fin est fournie
            $createdEnd = $request->input('created_end');
            $createdEnd = date('Y-m-d 23:59:59', strtotime($createdEnd));
            $query->where('documents.created_at', '<=', $createdEnd);
        }

        // 🔍 6. Recherche générale (optionnelle - garde la recherche globale)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('documents.identifier', 'LIKE', "%{$search}%")
                  ->orWhere('documents.description', 'LIKE', "%{$search}%")
                  ->orWhere('documents.beneficiaire', 'LIKE', "%{$search}%")
                  ->orWhere('types.name', 'LIKE', "%{$search}%");
            });
        }

        // ⚙️ Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $total = $query->count();

        $result = $query->offset(($page - 1) * $perPage)
                        ->limit($perPage)
                        ->get();

        return response()->json([
            'status_code' => 200,
            'message' => 'Documents filtrés avec succès',
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'total' => $total,
            'filters_applied' => [
                'identifier' => $request->input('identifier'),
                'type_name' => $request->input('type_name'),
                'type_id' => $request->input('type_id'),
                'date_information_period' => $request->filled('date_information_start') || $request->filled('date_information_end') 
                    ? [$request->input('date_information_start'), $request->input('date_information_end')]
                    : null,
                'created_period' => $request->filled('created_start') || $request->filled('created_end')
                    ? [$request->input('created_start'), $request->input('created_end')]
                    : null,
                'search' => $request->input('search'),
            ],
            'data' => $result,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors du filtrage des documents',
            'error' => $e->getMessage(),
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

    public function getActivities(Request $request)
    {
        // Récupération avec pagination (10 par page)
        $activities = ActivitiesLog::with('user')->paginate(10);

        // Retourner en JSON
        return response()->json([
            'status_code' => 200,
            'message' => 'Activités récupérées avec succès.',
            'data' => $activities
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
