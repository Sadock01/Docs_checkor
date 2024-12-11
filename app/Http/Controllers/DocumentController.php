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
                $query->whereRaw("identifier LIKE " % " . $search . " % "");
            }

            $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'The list of documents retrieved',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'items' => $result,
            ]);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error retrieving list documents', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Documents retrieved successfully',
            'data' => $documents,
        ]);
    }

    public function store(DocumentRequest $request)
    {
        try {
            $document = Document::create([
                'identifier' => $request->input('identifier'),
                'description' => $request->input('description'),
                'hash' => hash('sha256', $request->input('identifier')), // Génération automatique du hash
                'user_id' => Auth::user()->id

            ]);


            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document created successfully',
                'data' => $document
            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Error creating document',
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
            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document updated successfully',
                'data' => $document,
            ]);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error updating document', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(Document $document)
    {
        try {


            $document->delete();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Document deleted successfully',
                'data' => $document,

            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Error deleting document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
