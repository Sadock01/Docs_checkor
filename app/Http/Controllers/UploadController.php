<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
class UploadController extends Controller
{
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




public function uploadExcelfile(Request $request)
{
    try {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        $excel = $request->file('excel_file');

        // Lecture du fichier Excel avec PhpSpreadsheet
        $spreadsheet = IOFactory::load($excel->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $results = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Ignorer l'en-tête

            // Adapter selon tes colonnes : [filename, type, description, identifier, beneficiaire, date_information]
            [$filename, $type, $description, $identifier, $beneficiaire, $date_information] = array_pad($row, 6, null);

            $results[] = [
                'filename' => $filename,
                'status' => 'from_excel',
                'entities' => [
                    'type_document' => $type,
                    'description' => $description,
                    'identifier' => $identifier,
                    'beneficiaire' => $beneficiaire,
                    'date_information' => $date_information ?: "Aucune date mentionnée",
                ]
            ];
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Extraction terminée',
            'data' => $results
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur extraction fichier Excel : ' . $e->getMessage());

        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors du traitement du fichier Excel',
            'error' => $e->getMessage()
        ]);
    }
}

// public function uploadExcelfile(Request $request)
// {
//     try {
//         $request->validate([
//             'files' => 'nullable', // tableau de fichiers PDF
//             'excel_file' => 'nullable|file|mimes:xlsx,xls'
//         ]);

//         $files = $request->file('files');
//         $identifiers = $request->input('identifiers', []);
//         $excel = $request->file('excel_file');

//         if (!is_array($files) && $files !== null) {
//             $files = [$files];
//         }

//         if (!is_array($identifiers)) {
//             $identifiers = [$identifiers];
//         }

//         $results = [];

//         // Traitement des fichiers PDF
//         if ($files) {
//             foreach ($files as $index => $file) {
//                 if (!$file->isValid() || $file->getClientOriginalExtension() !== 'pdf') {
//                     $results[] = [
//                         'filename' => $file->getClientOriginalName(),
//                         'status' => 'error',
//                         'message' => 'Fichier invalide ou non-PDF'
//                     ];
//                     continue;
//                 }

//                 $response = Http::attach(
//                     'file',
//                     file_get_contents($file->getRealPath()),
//                     $file->getClientOriginalName()
//                 )->post('http://127.0.0.1:8001/extract-entities');

//                 if ($response->successful()) {
//                     $data = $response->json();

//                     $resultItem = [
//                         'filename' => $file->getClientOriginalName(),
//                         'status' => 'success',
//                         'entities' => $data['entities'] ?? [],
//                     ];

//                     if (isset($identifiers[$index]) && !empty($identifiers[$index])) {
//                         $resultItem['identifier'] = $identifiers[$index];
//                     }

//                     $results[] = $resultItem;
//                 } else {
//                     $results[] = [
//                         'filename' => $file->getClientOriginalName(),
//                         'status' => 'error',
//                         'message' => 'Erreur FastAPI : ' . $response->body()
//                     ];
//                 }
//             }
//         }

//         // Traitement du fichier Excel (facultatif)
//         if ($excel) {
//             // dd("ici");
//             $spreadsheet = IOFactory::load($excel->getPathname());
//             $sheet = $spreadsheet->getActiveSheet();
//             $rows = $sheet->toArray();

//             foreach ($rows as $index => $row) {
//                 if ($index === 0) continue; // Ignorer l'en-tête

//                 // [$filename, $type, $description, $identifier] = $row;
//                   [$filename, $type, $description, $identifier, $beneficiaire, $date_information] = array_pad($row, 6, null);

//                 $results[] = [
//                     'filename' => $filename,
//                     'status' => 'from_excel',
//                     'entities' => [
//                         'type_document' => $type,
//                         'description' => $description,
//                         'identifier' => $identifier,
//                         'beneficiaire' => $beneficiaire,
//                         'date_information' => $date_information ?: "Aucune date mentionnée",
//                     ]
//                 ];
                
//             }
//         }

//         return response()->json([
//             'status_code' => 200,
//             'message' => 'Extraction terminée',
//             'data' => $results
//         ]);
//     } catch (\Exception $e) {
//         \Log::error('Erreur extraction fichiers : ' . $e->getMessage());

//         return response()->json([
//             'status_code' => 500,
//             'message' => 'Erreur lors du traitement',
//             'error' => $e->getMessage()
//         ]);
//     }
// }


public function uploadAndExtractDocuments(Request $request)
{
    try {
        $request->validate([
            'files' => 'nullable', 
        ]);

        $files = $request->file('files');
        $identifiers = $request->input('identifiers', []);

        if (!is_array($files) && $files !== null) {
            $files = [$files];
        }
        if (!is_array($identifiers)) {
            $identifiers = [$identifiers];
        }

        $results = [];
        $excelAlreadyProcessed = false;

        if ($files) {
            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    $results[] = [
                        'filename' => $file->getClientOriginalName(),
                        'status' => 'error',
                        'message' => 'Fichier invalide'
                    ];
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());

                if ($extension === 'pdf') {
                    // Traitement PDF
                    $response = Http::attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post('http://127.0.0.1:8001/extract-entities');

                    if ($response->successful()) {
                        $data = $response->json();

                        $resultItem = [
                            'filename' => $file->getClientOriginalName(),
                            'status' => 'success',
                            'entities' => $data['entities'] ?? [],
                        ];

                        if (isset($identifiers[$index]) && !empty($identifiers[$index])) {
                            $resultItem['identifier'] = $identifiers[$index];
                        }

                        $results[] = $resultItem;
                    } else {
                        $results[] = [
                            'filename' => $file->getClientOriginalName(),
                            'status' => 'error',
                            'message' => 'Erreur FastAPI : ' . $response->body()
                        ];
                    }
                } elseif (in_array($extension, ['xlsx', 'xls'])) {
                    // Traitement Excel : un seul fichier autorisé
                    if ($excelAlreadyProcessed) {
                        $results[] = [
                            'filename' => $file->getClientOriginalName(),
                            'status' => 'error',
                            'message' => 'Un seul fichier Excel est autorisé.'
                        ];
                        continue;
                    }
                    $excelAlreadyProcessed = true;

                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    foreach ($rows as $rowIndex => $row) {
                        if ($rowIndex === 0) continue; // Ignorer l'en-tête

                        // On suppose la structure: [filename, type, description, identifier, beneficiaire, date_information]
                        [$filename, $type, $description, $identifier, $beneficiaire, $date_information] = array_pad($row, 6, null);
$typeModel = \App\Models\Type::firstOrCreate(
    ['name' => $type], // chercher par nom
    ['description' => 'Type creer depuis le document excel'] 
);
                        // Enregistrement en base
                        $document = \App\Models\Document::create([
                            'identifier' => $identifier,
                            'description' => $description,
                            'hash' => hash('sha256', $identifier),
                            'type_id' => $typeModel->id,
                            'beneficiaire' => $beneficiaire,
                            'date_information' => $date_information ?: "Aucune date mentionnée",
                        ]);

                        $results[] = [
                            'filename' => $filename,
                            'status' => 'saved',
                            'document_id' => $document->id,
                            'entities' => [
                                'type_document' => $type,
                                'description' => $description,
                                'identifier' => $identifier,
                                'beneficiaire' => $beneficiaire,
                                'date_information' => $date_information,
                            ]
                        ];
                    }
                } else {
                    $results[] = [
                        'filename' => $file->getClientOriginalName(),
                        'status' => 'error',
                        'message' => 'Extension non supportée'
                    ];
                }
            }
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Extraction et enregistrement terminés',
            'data' => $results
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur extraction fichiers : ' . $e->getMessage());

        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur lors du traitement',
            'error' => $e->getMessage()
        ]);
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
