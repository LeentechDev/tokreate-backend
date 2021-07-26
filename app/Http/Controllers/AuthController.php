<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;

class AuthController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request){
        $this->validate($request, [
            'user_name' => 'required|string',
            'user_email' => 'required|email|unique:users',
            'user_password' => 'required|string',
        ]);
        try {
            //registration
            $user = new User;
            $user->user_name = $request->input('user_name');
            $user->user_email = $request->input('user_email');
            $plainPassword = $request->input('user_password');
            $user->user_password = app('hash')->make($plainPassword);
            $user->user_role_id = $request->input('user_role_id') ? $request->input('user_role_id') : 1;
            $user->user_status = $request->input('user_status')? $request->input('user_status') : 1;
            $user->save();
            $user->id;

            //user profile            
            $user_profile = User_profile::create(
                [
                    "user_id" =>  $user->id,
                    "user_profile_full_name" => $request->input('user_profile_full_name'),
                ]
            );
            return response()->json(['user' => $user, 'message' => 'CREATED'], 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'User Registration Failed!'], 409);
        }
    }

    public function login(Request $request){
        $this->validate($request, [
            'user_email' => 'required|string',
            'user_password' => 'required|string',
        ]);
        $credentials = $request->only(['user_email', 'user_password']);
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }
}