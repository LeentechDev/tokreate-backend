<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Transaction;
use App\User_profile;
use App\Constants;
use App\Notifications;

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
            $user->user_role_id = $request->input('user_role_id') ? $request->input('user_role_id') : Constants::USER_ARTIST;
            $user->user_status = $request->input('user_status')? $request->input('user_status') : Constants::USER_STATUS_ACTIVE;
            $user->save();
            $user->id;
            $user_id = $user->user_id;
            //user profile            
            $user_data = User_profile::create(
                [
                    "user_id" =>  $user_id,
                    "user_profile_full_name" => $request->input('user_profile_full_name'),
                    "user_notification_settings" => "1",
                ]
            );

            $response=(object)[
                "success" => true,
                "result" => [
                    "user_profile" => $user_data,
                    "message" => 'Congratulation, your account has been successfully created',
                ]
            ];

            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'User Registration Failed!'], 409);
        }
    }

    public function login(Request $request){
        $this->validate($request, [
            'user_email' => 'required|string',
            'password' => 'required|string',
        ]);
        try {
            $credentials = $request->only(['user_email', 'password']);
            if (! $token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Incorrect Email or Password'], 401);
            }
            
            $user_data = User::where('user_id', Auth::user()->user_id)
            ->with([
                'profile',
                'tokens',
                'wallet' => function ($q) {
                    $q->orderBy('wallet_id', 'DESC')->first();
                },
                'notifications' => function ($q) {
                    $q->join('user_profiles', 'user_profiles.user_id', 'notification.notification_from');
                    $q->orderBy('id', 'DESC')->paginate(10);
                }
            ])
            ->where('user_role_id', Constants::USER_ARTIST)->with(['profile', 'tokens'])->first();

            
            if($user_data){
                foreach ($user_data['tokens'] as $key => $value) {
                    $user_data['tokens'][$key]->token_properties = json_decode(json_decode($value->token_properties));
                    $user_data['tokens'][$key]->transactions = $value->transactions()->orderBy('transaction_id','DESC')->get();
                }
                // $user_data['wallet'] = $user_data->wallet()->orderBy('wallet_id', 'DESC')->first();
                
                if(!$user_data->profile->user_profile_avatar){
                    $user_data->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
                }
            }else{
                return response()->json(['message' => 'Incorrect Email or Password'], 401);
            }

            return $this->respondWithToken($user_data,$token);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Login failed! Please try again.'], 409);
        }
    }


    public function admin_login(Request $request){
        $this->validate($request, [
            'user_email' => 'required|string',
            'password' => 'required|string',
        ]);
        /* try { */
            $credentials = $request->only(['user_email', 'password']);
            if (! $token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Incorrect Email or Password'], 401);
            }
            
            $user_data = User::where('user_id', Auth::user()->user_id)
            ->where('user_role_id', Constants::USER_ADMIN)->first();

            if($user_data){
                $user_data['profile'] = $user_data->profile;
                $notificationC = new Notifications;
                $user_data['notificaitons'] = $notificationC->adminNotifications();
                
                if(!$user_data->profile->user_profile_avatar){
                    $user_data->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
                }
            }else{
                return response()->json(['message' => 'Incorrect Email or Password'], 401);
            }

            return $this->respondWithToken($user_data,$token);
        /* }catch (\Exception $e) {
            return response()->json(['message' => 'Login failed! Please try again.'], 409);
        } */
    }
}