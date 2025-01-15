<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Règles de validation de la requête.
     */
    public function rules()
    {
        // Si la méthode est POST (store)
        if ($this->isMethod('post')) {
            return [
                'name' => 'required|string|unique:types',
                'description' => 'nullable|string',
            ];
        }

        // Si la méthode est PUT ou PATCH (update)
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'name' => 'required|string|unique:types,name,' . $this->route('id'),
                'description' => 'nullable|string',
            ];
        }

        return [];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages()
    {
        return [
            'name.required' => 'Le champ "nom" est obligatoire.',
            'name.unique' => 'Ce nom de type existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];
    }
}
