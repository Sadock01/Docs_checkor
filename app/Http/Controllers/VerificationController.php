<?php

namespace App\Http\Controllers;

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

    public function verify(Request $request)
{
    $request->validate([
        'identifier' => 'required|string',
        'file' => 'required|file|mimes:pdf|max:5120',
    ]);

    // 1. Upload temporaire
    $file = $request->file('file');
    $tempPath = $file->storeAs('temp_uploads', $file->getClientOriginalName());

    try {
        // 2. Appel au microservice FastAPI
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

        // 3. Récupération du document original
        $document = Document::with('type')->where('identifier', $request->identifier)->first();

        // Si aucun document trouvé
        if (!$document) {
            $status = 'Frauduleux';

            Verification::create([
                'identifier' => $request->identifier,
                'verification_date' => now(),
                'status' => $status,
            ]);

            return response()->json([
                'success' => false,
                'status' => 'invalid',
                'message' => 'Aucun document trouvé avec cet identifiant.',
            ], 404);
        }

        // 4. Comparaison
        $errors = [];

        if (!in_array($document->beneficiaire, $entities['beneficiaire'] ?? [])) {
            $errors['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
        }

        $extractedDescription = implode(' ', $entities['description'] ?? []);
        if ($document->description !== $extractedDescription) {
            $errors['description'] = 'La description ne correspond pas.';
        }

        if (!in_array($document->type->name, $entities['type_certificat'] ?? [])) {
            $errors['type_certificat'] = 'Le type de certificat ne correspond pas.';
        }

        // 5. Résultat
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

        Verification::create([
            'identifier' => $document->identifier,
            'verification_date' => now(),
            'status' => 'Authentique',
        ]);

        return response()->json([
            'success' => true,
            'status' => 'authentic',
            'message' => 'Le document est authentique.',
            'document' => [
                'identifier' => $document->identifier,
                'beneficiaire' => $document->beneficiaire,
                'description' => $document->description,
                'type_certificat' => $document->type->name,
            ],
        ], 200);

    } finally {
        // Supprimer le fichier temporaire
        Storage::delete($tempPath);
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
