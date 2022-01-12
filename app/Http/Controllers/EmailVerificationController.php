<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
// use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\User;

class EmailVerificationController extends Controller
{

    public function sendVerificationEmail(Request $request)
    {
        // try {
            if ($request->user()->hasVerifiedEmail()) {
                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Email address already verified!",
                    ]
                ];
                return response()->json($response, 201);
            }

            $user = $request->user();
            $user->email = $request->user()->user_email;
            $user->email_address = $request->user()->user_email;

            $user->sendEmailVerificationNotification();

            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Verification link sent.",
                ]
            ];
            return response()->json($response, 200);
        // } catch (\Exception $e) {
        //     return response()->json(['message' => 'Something went wrong. Try again later!'], 409);
        // }
    }

    public function verify(Request $request)
    {
        /* if ($request->user()->hasVerifiedEmail()) {
            return [
                'message' => 'Email already verified'
            ];
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return [
            'message'=>'Email has been verified'
        ]; */

        $this->validate($request, [
            'token' => 'required|string',
          ]);

        try {
            \Tymon\JWTAuth\Facades\JWTAuth::getToken();
            \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();

        } catch (\Exception $e) {
            return response()->json(['message' => 'Token Expired'], 409);
        }
            if ( ! $request->user() ) {
                $user = User::find($request->id);
            }else{
                $user = $request->user();
            }
            
            if ( $user->hasVerifiedEmail() ) {
                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Email address already verified!",
                    ]
                ];
                return response()->json($response, 200);
            }
            
            $user->markEmailAsVerified();
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Email address has been successfully verified!",
                ]
            ];
            return response()->json($response, 200);
        
    }
}