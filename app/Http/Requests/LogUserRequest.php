<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use illuminate\Contracts\Validation\Validator;

class LogUserRequest extends FormRequest
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
        return [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status_code' => 422,
            'success' => false,
            'error' => true,
            'message' => 'Identifiant ou mot de passe incorrect',
            'errorList' => $validator->errors()
        ]));
    }

    public function messages()
    {
        return [
            'password.required' => 'Mot de passe non fourni',
            'email.email' => 'Adresse email non valide',
            'email.exists' => 'Cette adresse n\'est lié à aucun compte',
            'email.required' => 'Email non fourni'
        ];
    }
}
