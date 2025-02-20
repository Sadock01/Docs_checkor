<?php

namespace App\Http\Controllers;


use App\Models\Document;

use App\Http\Requests\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Verification;
use App\Models\DocumentHistory;
use setasign\Fpdi\Fpdi;
;
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
    // public function showHistory($documentId)
    // {
    //     try {
    //         // Récupérer l'historique des modifications du document avec les utilisateurs
    //         $history = DocumentHistory::where('document_id', $documentId)
    //             ->with('user')  // Charger l'utilisateur avec chaque entrée d'historique
    //             ->get();

    //         if ($history->isEmpty()) {
    //             return response()->json(['message' => 'Aucune modification trouvée pour ce document.'], 404);
    //         }

    //         // Formater les données pour inclure le nom de l'utilisateur et la date
    //         $formattedHistory = $history->map(function ($entry) {
    //             return [
    //                 'modified_at' => $entry->modified_at, // La date de la modification
    //                 'user_firstname' => $entry->user->firstname, // Prénom de l'utilisateur
    //                 'user_lastname' => $entry->user->lastname, // Nom de l'utilisateur
    //                 'old_values' => json_decode($entry->old_values), // Anciennes valeurs du document
    //                 'new_values' => json_decode($entry->new_values), // Nouvelles valeurs du document
    //             ];
    //         });

    //         return response()->json([
    //             'status_code' => 200,
    //             'data' => $formattedHistory
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'statut_code' => 500,
    //             'message' => 'Erreur lors de la récupération de l\'historique.',
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }

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
            'status_code' => 200,
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

    public function uploadDocument(Request $request)
{
    try {
        // Vérifier si un fichier est envoyé
        if (!$request->hasFile('file')) {
            return response()->json(['erreur' => 'Aucun fichier trouvé'], 400);
        }

        $file = $request->file('file');

        // Vérifier si c'est un PDF
        if ($file->getClientOriginalExtension() !== 'pdf') {
            return response()->json(['erreur' => 'Seuls les fichiers PDF sont autorisés'], 400);
        }

        // Vérifier si un identifiant de document est fourni
        $documentId = $request->input('document_id');
        if (!$documentId) {
            return response()->json(['erreur' => 'L\'identifiant du document est requis'], 400);
        }

        // Vérifier si le document existe déjà dans la base de données
        $existingDocument = Document::find($documentId);

        // Si le document existe déjà, renvoyer une réponse
        if ($existingDocument) {
            return response()->json([
                'message' => 'Le document existe déjà.',
                'file_path' => asset('storage/' . $existingDocument->file_path)
            ], 200);
        }

        // Enregistrer le fichier PDF
        $fileName = 'document_' . $documentId . '_' . time() . '.pdf';
        $filePath = 'documents/' . $fileName;
        $file->storeAs('documents', $fileName, 'public');

        // Enregistrer le document dans la base de données
        $document = new Document();
        $document->id = $documentId;  // Utilisation de 'id' si c'est la clé primaire
        $document->file_path = $filePath;
        $document->save();

        return response()->json([
            'message' => 'Fichier uploadé avec succès',
            'file_path' => asset('storage/' . $document->file_path)
        ], 200);
    } catch (Exception $e) {
        \Log::error('Erreur upload document: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur lors de l\'upload', 'details' => $e->getMessage()], 500);
    }
}


    public function downloadDocumentWithQr($documentId)
    {
        try {
            // Récupérer le document de la base de données
            $document = Document::findOrFail($documentId);

            // Récupérer le chemin du fichier original
            $filePath = storage_path('app/public/' . $document->file_path);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Le fichier n\'existe pas.'], 404);
            }

            // Générer l'URL de vérification (ou toute autre URL que tu veux dans le QR Code)
            $verificationUrl = 'https://verification-platform.com';

            // Générer le QR Code
            QrCode::size(300)->generate($verificationUrl, storage_path('app/public/qr_code.png'));

            // Ajouter le QR Code au fichier PDF original
            $pdfFilePath = $this->addQrCodeToPdfWithWatermarker($filePath, storage_path('app/public/qr_code.png'));

            // Renvoi du fichier PDF modifié avec le QR Code ajouté
            return response()->download($pdfFilePath, 'document_with_qr.pdf');
        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur lors du téléchargement du fichier PDF.'], 500);
        }
    }

    private function addQrCodeToPdfWithWatermarker($filePath, $qrCode)
    {
        // Créer une instance de FPDI (qui étend FPDF)
        $pdf = new Fpdi();

        // Ajouter une page
        $pdf->AddPage();

        // Charger le fichier PDF existant
        $pdf->setSourceFile($filePath);

        // Importer la première page du PDF
        $tplIdx = $pdf->importPage(1);

        // Utiliser le modèle de la première page
        $pdf->useTemplate($tplIdx);

        // Convertir le QR Code (base64) en image
        $qrImage = imagecreatefromstring(base64_decode($qrCode));

        // Sauvegarder le QR Code en image
        $qrCodePath = 'qr_code.png';
        imagepng($qrImage, storage_path('app/public/' . $qrCodePath));

        // Ajouter l'image du QR Code sur le PDF (en haut à droite)
        $pdf->Image(storage_path('app/public/' . $qrCodePath), 180, 10, 30); // Positionner en haut à droite

        // Sauvegarder le fichier PDF avec le QR Code ajouté
        $modifiedPdfPath = 'documents/modified_document_with_qr.pdf';

        // Utiliser la méthode Output() de FPDF pour générer le fichier PDF
        $pdf->Output('F', storage_path('app/public/' . $modifiedPdfPath));

        return storage_path('app/public/' . $modifiedPdfPath);
    }

}
