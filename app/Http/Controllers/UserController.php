<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
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
    public function __construct(){
        $this->middleware('auth');
    }

    public function profile(){
        $user = User::where('user_id', Auth::user()->user_id)
        ->with([
            'profile',
            'tokens',
            'wallet' => function ($q) {
                $q->orderBy('wallet_id', 'DESC')->first();
            }
        ])->first();

        if(Auth::user()->user_role_id === Constants::USER_ADMIN){
            $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
            ->where('notification_to', 0)->limit(10)->get();
        }else{
            $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
            ->where('notification_to', Auth::user()->user_id)->limit(10)->get();
        }

        $user['notifications'] = $notifications;

        foreach ($user['tokens'] as $key => $value) {
           $user['tokens'][$key]->token_properties = json_decode(json_decode($value->token_properties));
           $user['tokens'][$key]->transactions = $value->transactions()->orderBy('transaction_id','DESC')->get();
        }

        $response=(object)[
            "success" => true,  
            "result" => [
                "datas" => $user,
            ]
        ];
        return response()->json($response, 200);
    }

    public function getUserTokens(Request $req){
        $user_id = Auth::user()->user_id;
        // var_dump($user_id );
        if($req->user_id){
            $user_id = $req->user_id;
        }

        $user = User::find($user_id);

        $page = $req->page;
        $limit = $req->limit;
        $tokens = $user->tokens();

        if($req->collection){
            /* if the token is not put on market */
            $tokens = $tokens->where('token_on_market', !Constants::TOKEN_ON_MARKET);
        }else{
            $tokens = $tokens->where('token_on_market', Constants::TOKEN_ON_MARKET);
        }

        $tokens = $tokens->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])->paginate($limit);

        foreach ($tokens as $key => $value) {
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
            // $tokens[$key]->transactions = $value->transactions()->orderBy('transaction_id','DESC')->get();
        }
        

        if($tokens->total()){
            $response=(object)[
                "success" => false,  
                "result" => [
                    "datas" => $tokens,
                ]
            ];
        }else{
            $response=(object)[
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

    public function allUsers(){
        $user=DB::select("SELECT * FROM `users`
            LEFT JOIN `user_profiles` ON `users`.`user_id`=`user_profiles`.`user_id`
            LEFT JOIN `wallets` ON `users`.`user_id`=`wallets`.`user_id` 
            WHERE `users`.`user_role_id`='1'");
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $user,
                ]
            ];
        return response()->json($response, 200);
    }

    public function singleUser($id){
        try {
            $user=DB::select("SELECT * FROM `users`
            LEFT JOIN `user_profiles` ON `users`.`user_id`=`user_profiles`.`user_id` 
            WHERE `users`.`user_id`='".$id."' and `users`.`user_role_id`='1'");
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $user,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User not found.'], 409);
        }

    }

    public function updateAccount(Request $request){
        function getRandomString($n) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            
            for ($i = 0; $i < $n; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
            
            return $randomString;
        }
        // try {
            $user_details = User_profile::where('user_id', Auth::user()->user_id);
            if($user_details){
                $request_data = $request->all();
                
                unset($request_data['user_name']);
                unset($request_data['user_email']);
                
                // $request_data['user_profile_avatar'] = url('app/images/default_avatar.jpg');
                $request_data['user_notification_settings'] = 1;
                $request_data['user_profile_completed'] = 1;
                $user_details->update($request_data);
                if($request->hasFile('user_profile_avatar')){
                    $user_file      = $request->file('user_profile_avatar');
                    $user_filename  = $user_file->getClientOriginalName();
                    $user_extension = $user_file->guessExtension();
                    $user_picture   = date('His').'-'.getRandomString(8);
                    $user_avatar    = $user_picture.'.'.$user_extension;
                    $destination_path = 'app/images/user_avatar';
                    if($user_file->move($destination_path, $user_avatar)){
                        $profile_path = url($destination_path.'/'.$user_avatar);
                        DB::statement("UPDATE `user_profiles` set `user_profile_avatar` = '".$profile_path."' Where `user_id` = '".Auth::user()->user_id."'");
                    }
                }
                $response=(object)[
                    "success" => true,
                    "result" => [
                        "message" => "Account has been successfully updated."
                    ]
                ];
                return response()->json($response, 201);
            }else{
                return response()->json(['message' => 'Account update failed!'], 409);
            }
        /* }catch (\Exception $e) {
            return response()->json(['message' => 'Account update failed!'], 409);
        } */
    }

    public function changePassword(Request $req){
        $old_password = $req->old_password;
        $new_password = $req->password;

        if(!$req->user_email){
            $user_current_password = Auth::user()->password;
        }else{
            $user_details = DB::where('user_email', $req->user_email)->first();
            $user_current_password = $user_details->password;
        }

        try{
            if (!app('hash')->check($old_password, $user_current_password)) {
                return response()->json(['message' => 'Your old password does not match our records'], 500);
            }else{
                $user = User::find(Auth::user()->user_id);
                $user->password = app('hash')->make($new_password);
                $user->save();
                
                $response=(object)[
                    "success" => true,
                    "result" => [
                        "message" => "Your password successfully changed."
                    ]
                ];
                return response()->json($response, 200);
            }
        }catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }

    public function changeNotifSettings(Request $req){
        try{
            $user_details = User_profile::where('user_id',Auth::user()->user_id)->first();

            $user_details->user_notification_settings = !$user_details->user_notification_settings;
        $user_details->save();
        }catch (\Exception $e) {
            return response()->json(['message' => 'Password change failed!'], 409);
        }
    }
}
