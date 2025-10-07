<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Jenssegers\Agent\Agent;
use App\Models\Document;
use App\Models\Verification;

class NewVerificationController extends Controller
{


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
        'identifier'       => $request->get('identifier'),
        'beneficiaire'     => $request->get('beneficiaire'),
        'type_name'        => $request->get('type_name'),
        'date_information' => $request->get('date_information'),
    ];

    $extractedData = null;
    $viaFile = $request->hasFile('file');

    // 🔹 Extraction des données si fichier PDF fourni
    if ($viaFile) {
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
                    'status'  => 'error',
                    'message' => 'Erreur lors de l\'extraction du fichier.',
                    'details' => $response->body(),
                ], 500);
            }

            $entities = $response->json('entities');

            // Écraser enteredData avec les valeurs extraites si présentes
            $enteredData = [
                'identifier'       => $entities['identifier'][0] ?? $enteredData['identifier'],
                'beneficiaire'     => $entities['beneficiaire'][0] ?? $enteredData['beneficiaire'],
                'type_name'        => $entities['type_certificat'][0] ?? $enteredData['type_name'],
                'date_information' => $entities['date_information'][0] ?? $enteredData['date_information'],
            ];

            $extractedData = $entities;

        } finally {
            Storage::delete($tempPath ?? null);
        }
    }

    // 🔹 Recherche du document UNIQUEMENT par identifiant
    $document = Document::with('type')
        ->where('identifier', $enteredData['identifier'])
        ->first();

    // Helper : découpe en mots (lettres/chiffres) et normalise en minuscules
    $tokenize = function(string $text): array {
        $text = mb_strtolower(trim($text));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text);
        return array_values(array_filter($tokens, fn($t) => $t !== ''));
    };

    $isMatching = true;
    $mismatches = [];

    if (!$document) {
        $isMatching = false;

        // Enregistrer la tentative de vérification même si document non trouvé
        $this->saveVerification($request, $enteredData, $extractedData, false, false, [], $viaFile);

        return response()->json([
            'success' => false,
            'status'  => 'not_found',
            'message' => "Aucun document correspondant trouvé avec l'identifiant fourni.",
            'entered_or_extracted_data' => $enteredData,
        ], 404);
    }

    // Vérification des champs

    if (!empty($enteredData['identifier']) 
        && $enteredData['identifier'] !== $document->identifier) {
        $isMatching = false;
        $mismatches['identifier'] = 'L’identifiant ne correspond pas.';
    }

    if (!empty($enteredData['beneficiaire'])) {
        $enteredTokens = $tokenize($enteredData['beneficiaire']);
        $docTokens     = $tokenize($document->beneficiaire);

        $foundAll = true;
        foreach ($enteredTokens as $token) {
            if (!in_array($token, $docTokens, true)) {
                $foundAll = false;
                break;
            }
        }

        if (!$foundAll) {
            $isMatching = false;
            $mismatches['beneficiaire'] = 'Le bénéficiaire ne correspond pas.';
        }
    }

    if (empty($enteredData['date_information']) || $enteredData['date_information'] !== $document->date_information) {
        $isMatching = false;
        $mismatches['date_information'] = 'La date est manquante ou incorrecte.';
    }

    if (!empty($enteredData['type_name']) 
        && $enteredData['type_name'] !== $document->type->name) {
        $isMatching = false;
        $mismatches['type_name'] = 'Le type de certificat ne correspond pas.';
    }

    // 🔹 Enregistrement de la vérification en base
    $this->saveVerification($request, $enteredData, $extractedData, true, $isMatching, $mismatches, $viaFile);

    // 🔹 Retour JSON
    return response()->json([
        'success' => false,
        'status'  => $isMatching ? 'authentic' : 'mismatch',
        'message' => $isMatching
            ? "Le document est authentique."
            : "Le document a été trouvé mais certaines informations ne correspondent pas.",
        'entered_or_extracted_data' => $enteredData,
        'document' => [
            'identifier'       => $document->identifier,
            'beneficiaire'     => $document->beneficiaire,
            'description'      => $document->description,
            'date_information' => $document->date_information,
            'type_certificat'  => $document->type->name,
        ],
        'mismatches' => $mismatches,
    ], 200);
}

/**
 * Enregistre la tentative de vérification dans la base.
 */
protected function saveVerification(Request $request, array $enteredData, ?array $extractedData, bool $documentFound, bool $isMatching, array $mismatches, bool $viaFile)
{
    $agent = new Agent();
    $agent->setUserAgent($request->userAgent());

    Verification::create([
        'user_id'        => null, // Pas d'authentification dans ton cas
        'ip_address'     => $request->ip(),          // IP publique de l'utilisateur
        'user_agent'     => $request->userAgent(),  // User-Agent complet
        'browser'        => $agent->browser(),      // Navigateur détecté
        'device_type'    => $agent->deviceType(),   // Type de l'appareil (mobile, desktop, bot...)
        'platform'       => $agent->platform(),     // OS de l'utilisateur
        'via_file'       => $viaFile,                // Vérification via fichier ou saisie manuelle
        'entered_data'   => json_encode($enteredData),    // Données saisies ou extraites
        'extracted_data' => $extractedData ? json_encode($extractedData) : null, // Données extraites du PDF (le cas échéant)
        'document_found' => $documentFound,          // Document trouvé ou non
        'is_matching'    => $isMatching,             // Correspondance exacte ou non
        'mismatches'     => !empty($mismatches) ? json_encode($mismatches) : null, // Les champs non concordants
    ]);
}

/**
 * Liste paginée des vérifications enregistrées, du plus récent au plus ancien.
 */
public function index()
{
    $verifications = \App\Models\Verification::latest()->paginate(10);

    return response()->json([
        'status_code' => 200,
        'current_page' => $verifications->currentPage(),
        'per_page' => $verifications->perPage(),
        'total' => $verifications->total(),
        'last_page' => $verifications->lastPage(),
        'verifications' => $verifications->map(function ($verification) {
            return [
                'id'              => $verification->id,
                'ip_address'      => $verification->ip_address,
                'user_agent'      => $verification->user_agent,
                'browser'         => $verification->browser,
                'device_type'     => $verification->device_type,
                'platform'        => $verification->platform,
                'via_file'        => (bool) $verification->via_file,
                'document_found'  => (bool) $verification->document_found,
                'is_matching'     => (bool) $verification->is_matching,
                'entered_data'    => json_decode($verification->entered_data, true),
                'extracted_data'  => $verification->extracted_data ? json_decode($verification->extracted_data, true) : null,
                'mismatches'      => $verification->mismatches ? json_decode($verification->mismatches, true) : null,
                'created_at'      => $verification->created_at->toDateTimeString(),
            ];
        }),
    ]);
}


}
