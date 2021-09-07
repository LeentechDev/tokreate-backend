<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Token;
use App\Edition;
use App\User_profile;
use App\Constants;
use App\Notifications;
use DB;

class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function profile()
    {
        $user = User::where('user_id', Auth::user()->user_id)
            ->with([
                'profile',
                'wallet' => function ($q) {
                    $q->orderBy('wallet_id', 'DESC')->first();
                }
            ])->first();

        if (Auth::user()->user_role_id === Constants::USER_ADMIN) {
            $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
                ->where('notification_to', 0)->limit(10)->get();
        } else {
            $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
                ->where('notification_to', Auth::user()->user_id)->limit(10)->get();
        }

        $user['notifications'] = $notifications;

        /* foreach ($user['tokens'] as $key => $value) {
            $user['tokens'][$key]->token_properties = json_decode(json_decode($value->token_properties));
            $user['tokens'][$key]->transactions = $value->transactions()->orderBy('transaction_id', 'DESC')->get();
        } */

        $response = (object)[
            "success" => true,
            "result" => [
                "datas" => $user,
            ]
        ];
        return response()->json($response, 200);
    }

    public function getUserTokens(Request $req)
    {
        $user_id = Auth::user()->user_id;
        $page = $req->page;
        $limit = $req->limit;

        if ($req->user_id) {
            $user_id = $req->user_id;
        } else {
            if ($req->collection != 2) {
                if ($req->collection) {
                    $on_market = 0;
                } else {
                    $on_market = 1;
                }
                /* DB::enableQueryLog(); */
                $tokens = Token::select(
                        'tokens.*',
                        'editions.edition_no',
                        'editions.on_market',
                        'editions.edition_id',
                        'editions.current_price as current_price',
                        'editions.owner_id',
                        DB::raw("(case when tokens.user_id = " . $user_id . " then remaining_token else edition_no end ) as remainToken")
                    )
                    ->rightJoin("editions", 'tokens.token_id', 'editions.token_id')
                    ->with([
                        'transactions' => function ($q) {
                            $q->orderBy('transaction_id', 'DESC');
                        }
                    ])
                    ->where('token_status', Constants::READY)
                    ->where('on_market', $on_market)
                    ->where('editions.owner_id', $user_id)
                    ->paginate($limit);
            } else {
                $tokens = Token::select(
                    'tokens.*',
                    'token_collectible_count as edition_no',
                    'token_starting_price as current_price'
                )
                    ->where('user_id', Auth::user()->user_id)
                    ->paginate($limit);
            }
        }
        /* print_r( DB::getQueryLog()); */

        foreach ($tokens as $key => $value) {
            $tokens[$key]->history = $value->history()->orderBy('id', 'DESC')->paginate(10);
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
            $tokens[$key]->mint_transactions = $value->transactions()->where('transaction_type', Constants::TRANSACTION_MINTING)->orderBy('transaction_id', 'ASC')->first();
            $tokens[$key]->owner = User::find($value->owner_id);
        }

        if ($tokens->total()) {
            $response = (object)[
                "success" => false,
                "result" => [
                    "datas" => $tokens,
                ]
            ];
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "datas" => null,
                    "message" => 'No artworks found.',
                ]
            ];
        }


        $response = response()->json($response, 200);

        return $response;
    }

    public function getCreateTokens()
    {
    }

    public function specificToken(Request $req)
    {

        /* if the given user is the creator of token get the details from token table */
        $token_details = Token::find($req->token_id);
        $user_id = Auth::user()->user_id;
        if ($req->user_id) {
            $user_id = $req->user_id;
        }

        if ($token_details) {

            /* if the given user is not the creator of token get the details from token table along with edition details */
            /* if ($token_details->user_id != Auth::user()->user_id) { */
                if ($req->edition_id) {
                    $token_details = Token::where('edition_id', $req->edition_id)
                        ->join('editions', 'editions.token_id', 'tokens.token_id')
                        ->first();
                    $token_details->owner = User::find($token_details->owner_id);
                } else {
                    return response()->json(['message' => 'Invalid Token'], 500);
                }
            /* } */

            if ($token_details) {
                $token_details->history = $token_details->history()->orderBy('id', 'DESC')->paginate(10);
                $token_details->transactions = $token_details->transactions()->orderBy('transaction_id', 'DESC')->paginate(10);
                $token_details->mint_transactions = $token_details->transactions()->where('transaction_type', Constants::TRANSACTION_MINTING)->orderBy('transaction_id', 'ASC')->first();
                $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));

                $response = (object)[
                    "success" => true,
                    "result" => [
                        "datas" => $token_details,
                        "message" => "Here are the details of the token.",
                    ]
                ];
                return response()->json($response, 200);
            }
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "Token not found.",
                ]
            ];
            return response()->json($response, 409);
        }
    }

    public function updateAccount(Request $request)
    {
        function getRandomString($n)
        {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';

            for ($i = 0; $i < $n; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }

            return $randomString;
        }
        try {
        $user_details = User_profile::where('user_id', Auth::user()->user_id);
        if ($user_details) {
            $request_data = $request->all();

            unset($request_data['user_name']);
            unset($request_data['user_email']);

            $request_data['user_notification_settings'] = 1;
            $request_data['user_profile_completed'] = 1;
            $user_details->update($request_data);
            if ($request->hasFile('user_profile_avatar')) {
                $user_file      = $request->file('user_profile_avatar');
                $user_filename  = $user_file->getClientOriginalName();
                $user_extension = $user_file->guessExtension();
                $user_picture   = date('His') . '-' . getRandomString(8);
                $user_avatar    = $user_picture . '.' . $user_extension;
                $destination_path = 'app/images/user_avatar';
                if ($user_file->move($destination_path, $user_avatar)) {
                    $profile_path = url($destination_path . '/' . $user_avatar);
                } else {
                    $profile_path = url('app/images/default_avatar.jpg');
                }
                DB::statement("UPDATE `user_profiles` set `user_profile_avatar` = '" . $profile_path . "' Where `user_id` = '" . Auth::user()->user_id . "'");
            }
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Account has been successfully updated."
                ]
            ];
            return response()->json($response, 201);
        } else {
            return response()->json(['message' => 'Account update failed!'], 409);
        }
        }catch (\Exception $e) {
            return response()->json(['message' => 'Account update failed!'], 409);
        }
    }

    public function changePassword(Request $req)
    {
        $old_password = $req->old_password;
        $new_password = $req->password;

        if (!$req->user_email) {
            $user_current_password = Auth::user()->password;
        } else {
            $user_details = DB::where('user_email', $req->user_email)->first();
            $user_current_password = $user_details->password;
        }

        try {
            if (!app('hash')->check($old_password, $user_current_password)) {
                return response()->json(['message' => 'Your old password does not match our records'], 500);
            } else {
                $user = User::find(Auth::user()->user_id);
                $user->password = app('hash')->make($new_password);
                $user->save();

                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Your password successfully changed."
                    ]
                ];
                return response()->json($response, 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }

    public function changeNotifSettings(Request $req)
    {
        try {
            $user_details = User_profile::where('user_id', Auth::user()->user_id)->first();

            $user_details->user_notification_settings = !$user_details->user_notification_settings;
            $user_details->save();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }

    public function changeEmailNotifSettings(Request $req)
    {
        try {
            $user_details = User_profile::where('user_id', Auth::user()->user_id)->first();

            $user_details->user_mail_notification = !$user_details->user_mail_notification;
            $user_details->save();
            var_dump($user_details);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }





    /* ADMIN FUNCTIONS */
    public function userManagementList(Request $request)
    {
        $searchTerm = $request->search_keyword;
        $managementList = User::where('user_role_id', Constants::USER_ARTIST)
            ->leftJoin('user_profiles', 'users.user_id', '=', 'user_profiles.user_profile_id')
            ->orderBy('user_status', 'ASC')
            ->where(function ($q) use ($searchTerm, $request) {
                if ($searchTerm) {
                    $q->where('user_profile_full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('user_email', 'like', '%' . $searchTerm . '%');
                }
            })
            ->orderBy($request->sort, $request->sort_dirc)
            ->paginate($request->limit);

        if ($managementList->total()) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $managementList,
                    "message" => "Here are the list of user management",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available user management",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function viewUserProfile(Request $request, $id)
    {

        $viewUserPortfolio = User::where('users.user_id', $id)
            ->join('user_profiles', 'users.user_id', 'user_profiles.user_id')
            ->first();

        if ($viewUserPortfolio) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $viewUserPortfolio,
                    "message" => "Here are the details of user portfolio details.",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "User portfolio not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function getUserSpecificMintingList(Request $request, $id)
    {

        $getMintingList = User::where('users.user_id', $id)
            ->join('tokens', 'users.user_id', 'tokens.user_id')
            ->join('transactions', 'tokens.token_id', 'transactions.transaction_token_id')
            ->where('transactions.transaction_type', Constants::TRANSACTION_MINTING)
            ->where('transactions.transaction_status', Constants::TRANSACTION_PENDING)
            ->where('tokens.token_status', Constants::PENDING)
            ->paginate($request->limit);

        if ($getMintingList->total()) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $getMintingList,
                    "message" => "Here are the details of artist/collector minting list.",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Artist/collector not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function getReadyTokens(Request $request, $user_id)
    {

        $getReadyPortfolio = Token::select(
            'tokens.*',
            'editions.edition_no',
            'editions.on_market',
            'editions.edition_id',
            DB::raw("(case when tokens.user_id != " . $user_id . " then editions.current_price else token_starting_price end ) as current_price"),
            DB::raw("(case when tokens.user_id = " . $user_id . " then remaining_token else edition_no end ) as remainToken")
        )
            ->where(
                DB::raw("(case when tokens.user_id = " . $user_id . " then tokens.user_id else editions.owner_id end)"),
                DB::raw("(case when tokens.user_id = " . $user_id . " then " . $user_id . " else " . $user_id . " end)")
            )
            ->leftJoin("editions", 'editions.token_id', 'tokens.token_id')
            /* ->with([
                'transactions' => function ($q) {
                    $q->orderBy('transaction_id', 'DESC');
                }, 
                'history' => function ($q) {
                    $q->orderBy('id', 'DESC');
                }, 
            ]) */
            ->groupBy(
                DB::raw(
                    '(case when tokens.user_id = ' . $user_id . ' then tokens.token_id else editions.edition_id end )'
                )
            )
            ->paginate($request->limit);

        if ($getReadyPortfolio->total()) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $getReadyPortfolio,
                    "message" => "Here are the porfolio list of specific users.",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Portfolio list not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function viewSpecificPortfolio(Request $request, $id)
    {
        $viewSpecificPortfolio = Token::where('tokens.token_id', $id)
            ->join('transactions', 'tokens.token_id', 'transactions.transaction_token_id')
            ->first();

        if ($viewSpecificPortfolio) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $viewSpecificPortfolio,
                    "message" => "Here are the details of specific portfolio details.",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Specific portfolio snot found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    /* public function getSpecificRequestMintingDetailsArtist(Request $request, $id){
        $viewSpecificRequestMinting = Token::where('tokens.token_id', $id)
        ->join('transactions', 'tokens.user_id', 'transactions.transaction_token_id')
        ->first();

        if($viewSpecificRequestMinting){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $viewSpecificRequestMinting,
                    "message" => "Here are the details of specific request minting details.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Specific request minting snot found.",
                ]
            ];
            return response()->json($response, 200);
        }
    } */

    public function deactivateUser(Request $request)
    {
        $deactivateUser = User::find($request->user_id);

        if ($deactivateUser) {
            $deactivateUser->update($request->all());
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "This Artist/Collector has been deactivated successfully",
                ]
            ];
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function activateUser(Request $request)
    {
        $activateUser = User::find($request->user_id);

        if ($activateUser) {
            $activateUser->update($request->all());
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "This Artist/Collector has been activated successfully",
                ]
            ];
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        }
        return response()->json($response, 200);
    }
}
