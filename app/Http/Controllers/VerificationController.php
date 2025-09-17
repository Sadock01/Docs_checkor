<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Verification;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Exception;
class VerificationController extends Controller
{
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

//     public function verify(Request $request)
// {
//     $request->validate([
//         'identifier' => 'required|string',
//         'file' => 'required|file|mimes:pdf|max:5120',
//     ]);

//     // 1. Upload temporaire
//     $file = $request->file('file');
//     $tempPath = $file->storeAs('temp_uploads', $file->getClientOriginalName());

//     try {
//         // 2. Appel au microservice FastAPI
//         $response = Http::attach(
//             'file',
//             Storage::get($tempPath),
//             $file->getClientOriginalName()
//         )->post('http://127.0.0.1:8001/extract-entities');

//         if ($response->failed()) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Erreur lors de l\'extraction du fichier.',
//                 'details' => $response->body()
//             ], 500);
//         }

//         $entities = $response->json('entities');

//         // 3. Récupération du document original
//         $document = Document::with('type')->where('identifier', $request->identifier)->first();

//         // Si aucun document trouvé
//         if (!$document) {
//             $status = 'Frauduleux';

//             Verification::create([
//                 'identifier' => $request->identifier,
//                 'verification_date' => now(),
//                 'status' => $status,
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'status' => 'invalid',
//                   'message' => "Aucun document trouvé avec l'identifiant : {$request->identifier}",
//             ], 404);
//         }

//         // 4. Comparaison
//         $errors = [];

//         if (!in_array($document->beneficiaire, $entities['beneficiaire'] ?? [])) {
//             $errors['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
//         }

//         $extractedDescription = implode(' ', $entities['description'] ?? []);
//         if ($document->description !== $extractedDescription) {
//             $errors['description'] = 'La description ne correspond pas.';
//         }

//         if (!in_array($document->type->name, $entities['type_certificat'] ?? [])) {
//             $errors['type_certificat'] = 'Le type de certificat ne correspond pas.';
//         }

//         // 5. Résultat
//         if (count($errors)) {
//             Verification::create([
//                 'identifier' => $document->identifier,
//                 'verification_date' => now(),
//                 'status' => 'Frauduleux',
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'status' => 'not_authentic',
//                 'message' => 'Document non authentique.',
//                 'reasons' => $errors,
//             ], 200);
//         }

//         Verification::create([
//             'identifier' => $document->identifier,
//             'verification_date' => now(),
//             'status' => 'Authentique',
//         ]);

//         return response()->json([
//             'success' => true,
//             'status' => 'authentic',
//             'message' => 'Le document est authentique.',
//             'document' => [
//                 'identifier' => $document->identifier,
//                 'beneficiaire' => $document->beneficiaire,
//                 'description' => $document->description,
//                 'type_certificat' => $document->type->name,
//             ],
//         ], 200);

//     } finally {
//         // Supprimer le fichier temporaire
//         Storage::delete($tempPath);
//     }
// }


// public function verify(Request $request)
// {
//     // Validation : le fichier devient optionnel
//     $request->validate([
//         'identifier' => 'required|string',
//         'file' => 'nullable|file|mimes:pdf|max:5120',
//     ]);

//     // Chercher le document dans la base
//     $document = Document::with('type')->where('identifier', $request->identifier)->first();

//     // Si aucun document trouvé
//     if (!$document) {
//         Verification::create([
//             'identifier' => $request->identifier,
//             'verification_date' => now(),
//             'status' => 'Frauduleux',
//         ]);

//         return response()->json([
//             'success' => false,
//             'status' => 'invalid',
//             'message' => "Aucun document trouvé avec l'identifiant : {$request->identifier}",
//             'entered_identifier' => $request->identifier
//         ], 404);
//     }

//     // Si aucun fichier, on fait juste un check basique
//     if (!$request->hasFile('file')) {
//         Verification::create([
//             'identifier' => $document->identifier,
//             'verification_date' => now(),
//             'status' => 'Authentique',
//         ]);

//         return response()->json([
//             'success' => true,
//             'status' => 'mi-authentic',
//             'message' => 'Le document est authentique (vérification par identifiant uniquement).',
//             'document' => [
//                 'identifier' => $document->identifier,
//                 'beneficiaire' => $document->beneficiaire,
//                 'description' => $document->description,
//                 'type certificat' => $document->type->name,
//             ],
//         ], 200);
//     }

//     // Si fichier fourni → on garde ton code actuel
//     $file = $request->file('file');
//     $tempPath = $file->storeAs('temp_uploads', $file->getClientOriginalName());

//     try {
//         // Appel à FastAPI
//         $response = Http::attach(
//             'file',
//             Storage::get($tempPath),
//             $file->getClientOriginalName()
//         )->post('http://127.0.0.1:8001/extract-entities');

//         if ($response->failed()) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Erreur lors de l\'extraction du fichier.',
//                 'details' => $response->body()
//             ], 500);
//         }

//         $entities = $response->json('entities');
// if (empty($entities) || (
//     empty($entities['beneficiaire']) &&
//     empty($entities['description']) &&
//     empty($entities['type_certificat'])
// )) {
//     return response()->json([
//         'success' => false,
//         'status' => 'extraction_failed',
//         'message' => 'Le document n\'a pas pu être lu. Les données extraites sont vides.'
//     ], 422); // 422 = données non exploitables
// }
//         // Comparaison
//         $errors = [];

//         if (!in_array($document->beneficiaire, $entities['beneficiaire'] ?? [])) {
//             $errors['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
//         }

//         $extractedDescription = implode(' ', $entities['description'] ?? []);
//         if ($document->description !== $extractedDescription) {
//             $errors['description'] = 'La description ne correspond pas.';
//         }

//         if (!in_array($document->type->name, $entities['type_certificat'] ?? [])) {
//             $errors['type certificat'] = 'Le type de certificat ne correspond pas.';
//         }

//         if (count($errors)) {
//             Verification::create([
//                 'identifier' => $document->identifier,
//                 'verification_date' => now(),
//                 'status' => 'Frauduleux',
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'status' => 'not_authentic',
//                 'message' => 'Document non authentique.',
//                 'reasons' => $errors,
//             ], 200);
//         }

//         Verification::create([
//             'identifier' => $document->identifier,
//             'verification_date' => now(),
//             'status' => 'Authentique',
//         ]);

//         return response()->json([
//             'success' => true,
//             'status' => 'authentic',
//             'message' => 'Le document est authentique.',
//             'document' => [
//                 'identifier' => $document->identifier,
//                 'beneficiaire' => $document->beneficiaire,
//                 'description' => $document->description,
//                 'type_certificat' => $document->type->name,
//             ],
//         ], 200);

//     } finally {
//         Storage::delete($tempPath ?? null);
//     }
// }



public function verify(Request $request)
{
    // Validation
    $request->validate([
        'identifier' => 'required|string',
        'file' => 'nullable|file|mimes:pdf|max:5120',
    ]);

    
    $document = Document::with('type')->where('identifier', $request->identifier)->first();

    if (!$document) {
        Verification::create([
            'identifier' => $request->identifier,
            'verification_date' => now(),
            'status' => 'Frauduleux',
        ]);

        return response()->json([
            'success' => false,
            'status' => 'invalid',
            'message' => "Aucun document trouvé avec l'identifiant : {$request->identifier}",
            'entered_identifier' => $request->identifier
        ], 404);
    }

    // 2. Vérification par identifiant uniquement
    if (!$request->hasFile('file')) {
        $verification = Verification::create([
            'identifier' => $document->identifier,
            'verification_date' => now(),
            'status' => 'Authentique',
        ]);

        $formattedDate = Carbon::parse($verification->verification_date)
            ->locale('fr')
            ->translatedFormat('d F Y à H\hi');

        return response()->json([
            'success' => true,
            'status' => 'mi-authentic',
            'message' => "Document vérifié le {$formattedDate} — statut : valide (par identifiant uniquement).",
            'document' => [
                'identifier' => $document->identifier,
                'beneficiaire' => $document->beneficiaire,
                'description' => $document->description,
                'type_certificat' => $document->type->name,
            ],
        ], 200);
    }

    // 3. Vérification avec fichier
    $file = $request->file('file');
    $tempPath = $file->storeAs('temp_uploads', $file->getClientOriginalName());

    try {
        $response = Http::attach(
            'file',
            Storage::get($tempPath),
            $file->getClientOriginalName()
        )->post('http://127.0.0.1:8001/extract-entities');

        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'extraction du fichier.',
                'details' => $response->body()
            ], 500);
        }

        $entities = $response->json('entities');

        // Si extraction vide
        if (empty($entities) || (
            empty($entities['beneficiaire']) &&
            empty($entities['description']) &&
            empty($entities['type_certificat'])
        )) {
            return response()->json([
                'success' => false,
                'status' => 'extraction_failed',
                'message' => 'Le document n\'a pas pu être lu. Les données extraites sont vides.'
            ], 422);
        }

        // Comparaison
        $errors = [];

        if (!in_array($document->beneficiaire, $entities['beneficiaire'] ?? [])) {
            $errors['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
        }

        $extractedDescription = implode(' ', $entities['description'] ?? []);
        // dd($extractedDescription, $document->description);
        if ($document->description !== $extractedDescription) {
            $errors['description'] = 'La description ne correspond pas.';
        }

        if (!in_array($document->type->name, $entities['type_certificat'] ?? [])) {
            $errors['type_certificat'] = 'Le type de certificat ne correspond pas.';
        }

        if (count($errors)) {
            Verification::create([
                'identifier' => $document->identifier,
                'verification_date' => now(),
                'status' => 'Frauduleux',
            ]);

            return response()->json([
                'success' => false,
                'status' => 'not_authentic',
                'message' => 'Document non authentique.',
                'reasons' => $errors,
            ], 200);
        }

        // 4. Document authentique
        $verification = Verification::create([
            'identifier' => $document->identifier,
            'verification_date' => now(),
            'status' => 'Authentique',
        ]);

        $formattedDate = Carbon::parse($verification->verification_date)
            ->locale('fr')
            ->translatedFormat('d F Y à H\hi');

        return response()->json([
            'success' => true,
            'status' => 'authentic',
            'message' => "Document vérifié le {$formattedDate} — statut : valide.",
            'document' => [
                'identifier' => $document->identifier,
                'beneficiaire' => $document->beneficiaire,
                'description' => $document->description,
                'type_certificat' => $document->type->name,
            ],
        ], 200);

    } finally {
        Storage::delete($tempPath ?? null);
    }
}

public function verifyDoc(Request $request)
{
    // 🔹 Validation des entrées
    $request->validate([
        'identifier' => 'nullable|string',
        'beneficiaire' => 'nullable|string',
        'type_name' => 'nullable|string',
        'date_information' => 'nullable|string',
        'file' => 'nullable|file|mimes:pdf|max:5120',
    ]);

    // 🔹 Données saisies par l'utilisateur
    $enteredData = [
        'identifier' => $request->get('identifier'),
        'beneficiaire' => $request->get('beneficiaire'),
        'type_name' => $request->get('type_name'),
        'date_information' => $request->get('date_information'),
    ];

    // 🔹 Si fichier PDF fourni, extraire les données
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $tempPath = $file->storeAs('temp_uploads', $file->getClientOriginalName());

        try {
            $response = Http::attach(
                'file',
                Storage::get($tempPath),
                $file->getClientOriginalName()
            )->post('http://127.0.0.1:8001/extract-entities');

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Erreur lors de l\'extraction du fichier.',
                    'details' => $response->body(),
                ], 500);
            }

            $entities = $response->json('entities');

            // On remplace enteredData par les valeurs extraites si disponibles
            $enteredData = [
                'identifier' => $entities['identifier'][0] ?? $enteredData['identifier'],
                'beneficiaire' => $entities['beneficiaire'][0] ?? $enteredData['beneficiaire'],
                'type_name' => $entities['type_certificat'][0] ?? $enteredData['type_name'],
                'date_information' => $entities['date_information'][0] ?? $enteredData['date_information'],
            ];

        } finally {
            Storage::delete($tempPath ?? null);
        }
    }

    // 🔹 Requête pour trouver le document correspondant
    $query = Document::with('type');

    if (!empty($enteredData['identifier'])) {
        $query->where('identifier', $enteredData['identifier']);
    }
    if (!empty($enteredData['beneficiaire'])) {
        $query->where('beneficiaire', 'like', '%' . $enteredData['beneficiaire'] . '%');
    }
    if (!empty($enteredData['date_information'])) {
        $query->where('date_information', $enteredData['date_information']);
    }
    if (!empty($enteredData['type_name'])) {
        $query->whereHas('type', function ($q) use ($enteredData) {
            $q->where('name', $enteredData['type_name']);
        });
    }

    $document = $query->first();

    // 🔹 Si aucun document trouvé
    if (!$document) {
        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'message' => "Aucun document correspondant trouvé avec les informations fournies.",
            'entered_or_extracted_data' => $enteredData,
        ], 404);
    }

    // 🔹 Vérification des correspondances
    $isMatching = true;
    $mismatches = [];

    // Identifier strict
    if ($enteredData['identifier'] && $enteredData['identifier'] !== $document->identifier) {
        $isMatching = false;
        $mismatches['identifier'] = 'L’identifiant ne correspond pas.';
    }

    // Bénéficiaire partiel et insensible à la casse
    if (!empty($enteredData['beneficiaire'])) {
        $enteredBenef = strtolower($enteredData['beneficiaire']);
        $docBenef = strtolower($document->beneficiaire);

        if (strpos($docBenef, $enteredBenef) === false) {
            $isMatching = false;
            $mismatches['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
        }
    }

    // Date stricte
    if ($enteredData['date_information'] && $enteredData['date_information'] !== $document->date_information) {
        $isMatching = false;
        $mismatches['date_information'] = 'La date ne correspond pas.';
    }

    // Type stricte
    if ($enteredData['type_name'] && $enteredData['type_name'] !== $document->type->name) {
        $isMatching = false;
        $mismatches['type_name'] = 'Le type de certificat ne correspond pas.';
    }

    // 🔹 Retour JSON
    return response()->json([
        'success' => $isMatching,
        'status' => $isMatching ? 'authentic' : 'mismatch',
        'message' => $isMatching
            ? "Le document est authentique."
            : "Les informations saisies/extraites ne correspondent pas totalement au document.",
        'entered_or_extracted_data' => $enteredData,
        'document' => [
            'identifier' => $document->identifier,
            'beneficiaire' => $document->beneficiaire,
            'description' => $document->description,
            'date_information' => $document->date_information,
            'type_certificat' => $document->type->name,
        ],
        'mismatches' => $mismatches,
    ], 200);
}




public function getVerificationHistory(Request $request)
    {
        try {
            $query = Verification::query();
            $perPage = 11;
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

     public function getVerificationStats()
    {
        try {
            $stats = Verification::selectRaw("DATE(verification_date) as date, status, COUNT(*) as count")
                ->groupBy('date', 'status')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'status_code' => 200,
                'message' => 'Statistiques des vérifications récupérées avec succès.',
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Erreur lors de la récupération des statistiques.',
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

      public function totalVerifications()
    {
        $total = Verification::count();

        return response()->json([
            'success' => true,
            'total_verifications' => $total,
        ]);
    }
}
