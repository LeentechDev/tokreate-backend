<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\User;
use App\Transaction;
use App\User_profile;
use App\Constants;
use App\Notifications;
use App\ResetPassword;
use App\Fund;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    protected const MERCHANT_ID = 'LEENTECH';
    protected const MERCHANT_PASS = 'Da5qgHfEw3zN';
    protected const MERCHANT_API_KEY = 'bec973b72e20e653ddc54c0b37cbf18a254b6928';
    protected const MODE = 'development';
    public function register(Request $request)
    {
        $this->validate($request, [
            'user_name' => 'required|string',
            'user_email' => 'required|email|unique:users',
            'password' => 'required|string',
        ]);
        /* try { */
            //registration
            $user = new User;
            $user->user_name = $request->input('user_name');
            $user->user_email = $request->input('user_email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);
            $user->user_role_id = $request->input('user_role_id') ? $request->input('user_role_id') : Constants::USER_ARTIST;
            $user->user_status = $request->input('user_status') ? $request->input('user_status') : Constants::USER_STATUS_ACTIVE;
            $user->save();
            $user->id;
            $user_id = $user->user_id;
            //user profile            
            $user_data = User_profile::create(
                [
                    "user_id" =>  $user_id,
                    "user_profile_avatar" => url('app/images/default_avatar.jpg'),
                    "user_profile_full_name" => $request->input('user_profile_full_name'),
                    "user_notification_settings" => "1",
                ]
            );

            $fund = Fund::create(
                [
                    "user_id" =>  $user_id,
                ]
            );

            $response = (object)[
                "success" => true,
                "result" => [
                    "user_profile" => $user_data,
                    "message" => 'Congratulation, your account has been successfully created',
                ]
            ];

            return response()->json($response, 201);
        /* } catch (\Exception $e) {
            return response()->json(['message' => 'User Registration Failed!'], 409);
        } */
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'user_email' => 'required|string',
            'password' => 'required|string',
        ]);
        try {
            $credentials = $request->only(['user_email', 'password']);
            if (!$token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Your email and/or password is incorrect.'], 401);
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


            if ($user_data) {
                if ($user_data->user_status === Constants::USER_STATUS_ACTIVE) {
                    foreach ($user_data['tokens'] as $key => $value) {
                        $user_data['tokens'][$key]->token_properties = json_decode(json_decode($value->token_properties));
                        $user_data['tokens'][$key]->transactions = $value->transactions()->orderBy('transaction_id', 'DESC')->get();
                    }
                    // $user_data['wallet'] = $user_data->wallet()->orderBy('wallet_id', 'DESC')->first();

                    if (!$user_data->profile->user_profile_avatar) {
                        $user_data->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
                    }
                } else {
                    return response()->json(['message' => 'Oops! Your account is deactivated.'], 401);
                }
            } else {
                return response()->json(['message' => 'Your email and/or password is incorrect.'], 401);
            }

            return $this->respondWithToken($user_data, $token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Login failed! Please try again.'], 409);
        }
    }


    public function admin_login(Request $request)
    {
        $this->validate($request, [
            'user_email' => 'required|string',
            'password' => 'required|string',
        ]);
        try {
            $credentials = $request->only(['user_email', 'password']);
            if (!$token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Your email and/or password is incorrect.'], 401);
            }

            $user_data = User::where('user_id', Auth::user()->user_id)
                ->where('user_role_id', Constants::USER_ADMIN)->first();

            if ($user_data) {
                if ($user_data->user_status === Constants::USER_STATUS_ACTIVE) {
                    $user_data['profile'] = $user_data->profile;
                    $notificationC = new Notifications;
                    $user_data['notifications'] = $notificationC->adminNotifications();

                    if (!$user_data->profile->user_profile_avatar) {
                        $user_data->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
                    }
                } else {
                    return response()->json(['message' => 'Oops! Your account is deactivated.'], 401);
                }
            } else {
                return response()->json(['message' => 'Your email and/or password is incorrect.'], 401);
            }

            return $this->respondWithToken($user_data, $token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Login failed! Please try again.'], 409);
        }
    }

    public function resetPassword(Request $request)
    {

        /* try{ */
        $user_details = User::where('user_email', $request->user_email)->first();

        $token = str_random(60);
        $validity = '1 day';

        if ($user_details) {
            ResetPassword::create([
                'email_address' => $request->user_email,
                'token' => $token,
                'validity' => $validity,
            ]);

            Mail::send('mail.reset-password', ['token' => $token], function ($message) use ($user_details) {
                $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Reset Password');
                $message->from('support@tokreate.com', 'Tokreate');
            });
        } else {
            return response()->json(['message' => 'Sorry, the email address provided is not registered in our website.'], 409);
        }

        $response = (object)[
            "success" => true,
            "result" => [
                "message" => 'Thank you! Please check your email for reset password instruction.',
            ]
        ];
        return response()->json($response, 200);

        /* }catch (\Exception $e) {
            return response()->json(['message' => 'Unable to send email right now.'], 409);
        } */
    }

    public function validateTokenRP(Request $req)
    {

        try {
            $details = ResetPassword::where('token', $req->token)->first();

            $date_exp = strtotime($details->created_at . " + " . $details->validity);

            if ($date_exp > strtotime("now")) {
                return response()->json(['message' => 'valid', 'email_address' => $details->email_address], 200);
            } else {
                return response()->json(['message' => 'Your password reset link is expired'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong.'], 409);
        }
    }

    public function changePassword(Request $req)
    {
        $new_password = $req->password;

        try {
            $user = User::where('user_email', $req->email_address)->first();

            if ($user) {
                $user->password = app('hash')->make($new_password);
                $user->save();

                $response = (object)[
                    "success" => true,
                    "result" => [
                        "data" => [
                            'user_role' => $user->user_role_id
                        ],
                        "message" => "Your password successfully updated."
                    ]
                ];
                return response()->json($response, 200);
            } else {
                return response()->json(['message' => 'Sorry, the email address provided is not registered in website.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }
}
