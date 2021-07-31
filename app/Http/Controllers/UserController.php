<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\User;
use  App\User_profile;
use  App\Constants;
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
        $user = User::where('user_id', Auth::user()->user_id)->first();
        $user['wallet'] = $user->wallet;

        if(!$user->profile->user_profile_avatar){
            $user->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
        }

        $user['profile'] = $user->profile;
        $user['tokens'] = $user->tokens;
        $response=(object)[
            "success" => true,  
            "result" => [
                "datas" => $user,
            ]
        ];
        return response()->json($response, 200);
    }

    public function getUserTokens(Request $req){
        $user = User::where('user_id', $req->user_id)->first();

        $page = $req->page;
        $limit = $req->limit;
        $tokens = $user->tokens();

        if($req->collection){
            /* if the token is not put on market */
            $tokens = $tokens->where('token_status', Constants::COLLECTION);
        }else{
            $tokens = $tokens->where('token_status', Constants::FORSALE);
        }

        $tokens = $tokens->paginate($limit);

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
        try {
            $user_details = User_profile::where('user_id', Auth::user()->user_id);
            if($user_details){
                $request_data = $request->all();
                
                unset($request_data['user_name']);
                unset($request_data['user_email']);
                unset($request_data['user_profile_avatar']);
                
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
        }catch (\Exception $e) {
            return response()->json(['message' => 'Account update failed!'], 409);
        }
    }
}
