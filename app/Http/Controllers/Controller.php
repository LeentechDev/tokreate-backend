<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function respondWithToken($user_data, $token)
    {

        $response = (object)[
            "success" => true,
            "result" => [
                "token" => $token,
                "token_type" => 'bearer',
                "expires_in" => Auth::factory()->getTTL() * 60,
                "user_profile" => $user_data,
                "message" => 'Welcome, ' . $user_data->profile->user_profile_full_name,
            ]
        ];

        return response()->json($response, 200);
    }
}
