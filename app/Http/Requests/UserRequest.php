<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize()
    {
        return true; 
    }

    /**
     * Règles de validation pour la requête.
     */
    public function rules()
    {
        // Vérifie si c'est une requête de mise à jour ou de création
        if ($this->isMethod('post')) {
            // Règles pour la création (store)
            return [
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'role_id' => 'required|exists:roles,id',
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'firstname' => 'sometimes|required|string|max:255',
                'lastname' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $this->user->id,
                'password' => 'sometimes|min:8',
                'status' => 'sometimes|boolean',
                'role_id' => 'sometimes|required|exists:roles,id',
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
            'firstname.required' => 'Le prénom est requis.',
            'lastname.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'role_id.required' => 'Le rôle est requis.',
            'role_id.exists' => 'Le rôle sélectionné est invalide.',
        ];
    }
}
