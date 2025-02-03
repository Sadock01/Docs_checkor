<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Vérifie si c'est une requête de création ou de mise à jour
        if ($this->isMethod('post')) {
            return $this->storeRules();
        }

        if ($this->isMethod('put')) {
            return $this->updateRules();
        }
        return [];
    }
    // Règles pour la création d'un document
    private function storeRules()
    {
        return [
            'identifier' => 'required|string|unique:documents',
            'description' => 'required|string|max:200',
            'type_id'=> 'required|exists:types,id',
            
        ];
    }


    private function updateRules()
    {
        return [
            'identifier' => 'required|string',
            'description' => 'required|string|max:200',
            'type_id' => 'required|exists:types,id', 
           
        ];
    }


    public function messages()
    {
        return [
            'identifier.required' => 'L\'identifiant du document est obligatoire.',
            'identifier.unique' => 'Cet identifiant est déjà utilisé.',
            'description' => 'La description pour ce document est requise',
            'type_id.required' => 'Ce document doit avoir un type'
        ]; 
    }
}
