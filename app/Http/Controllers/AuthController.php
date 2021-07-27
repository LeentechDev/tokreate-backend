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
            'password' => 'required|string',
        ]);
        try {
            //registration
            $user = new User;
            $user->user_name = $request->input('user_name');
            $user->user_email = $request->input('user_email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);
            $user->user_role_id = $request->input('user_role_id');
            $user->user_status = $request->input('user_status');
            $user->save();
            $user->id;
            $user_id = $user->user_id;
            //user profile            
            $user_profile = User_profile::create(
                [
                    "user_id" =>  $user_id,
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
            'password' => 'required|string',
        ]);
        $credentials = $request->only(['user_email', 'password']);
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }
}