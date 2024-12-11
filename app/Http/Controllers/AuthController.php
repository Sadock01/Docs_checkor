<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LogUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function login(LogUserRequest $request){
        $request->validate([
           'email' => 'required|email|exists:users,email',
           'password' => 'required'
       ]);
   
       $user = User::where('email', $request->email)->first();
       if(!$user || !Hash::check($request->password, $user->password)){
           return[
               'message' => 'The provided credentials are incorrect.',401
           ];
       }
      
       $user = Auth::user();
       
    $token = $user->createToken('auth_token')->plainTextToken;

     
       return [
        'status_code' =>200,
        'status_message' => 'Login successful.',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer',
           
       ];
      }
   
      public function logout(Request $request){
       $request->user()->currentAccessToken()->delete();
       return[
           'message' => 'You are logged out'
       ];
      }
}
