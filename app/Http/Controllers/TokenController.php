<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\User_profile;
use  App\Token;
use DB;

class TokenController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function __construct(){
        $this->middleware('auth');
    }

    public function mintRequest(Request $request){
        $this->validate($request, [
            'user_id' => 'required|string',
            'token_collectible' => 'required|integer',
            'token_collectible_count' => 'required|integer',
            'token_title' => 'required|string',
            'token_description' => 'required|string',
            'token_starting_price' => 'required|string',
            'token_royalty' => 'required|int',
            'token_filename' => 'required',
            'token_saletype' => 'required|string',
            'token_status' => 'required|string',
        ]);
        function getRandomString($n) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            
            for ($i = 0; $i < $n; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
            
            return $randomString;
        }
        $wallet_details = DB::select("SELECT * from wallets WHERE user_id='".$request->input('user_id')."' and wallet_status=1");
        if($wallet_details){
            try {
                $token = new Token;
                $token_file             = $request->file('token_filename');
                $token_original_name    = $token_file->getClientOriginalName();
                $token_extension        = $token_file->guessExtension();
                $token_new_file_name    = date('His').'-'.getRandomString(8);
                $generated_token        = $token_new_file_name.'.'.$token_extension;
                // destination path for production
                // $token_destination_path = storage_path('app/images/user_avatar');
                $token_destination_path = 'app/images/user_avatar';
                $token_file->move($token_destination_path, $generated_token);
                

                $token->user_id = $request->input('user_id');
                $token->token_collectible = $request->input('token_collectible');
                $token->token_collectible_count = $request->input('token_collectible_count');
                $token->token_title = $request->input('token_title');
                $token->token_description = $request->input('token_description');
                $token->token_starting_price = $request->input('token_starting_price');
                $token->token_royalty = $request->input('token_royalty');
                $token->token_filename = $generated_token;
                $token->token_saletype = $request->input('token_saletype');
                $token->token_status = $request->input('token_status');
                $token->save();
                $token_id = $token->token_id;
                // $user_profile = User_profile::create(
                //     [
                //         "user_id" =>  $user_id,
                //         "user_profile_full_name" => $request->input('user_profile_full_name'),
                //     ]
                // );
                return response()->json(['user' => $token, 'message' => 'Your artwork has been successfully request for minting'], 201);
            }catch (\Exception $e) {
                return response()->json(['message' => 'User Registration Failed!'], 409);
            }
        }else{
            return response()->json(['message' => 'Please set up your wallet first'], 409);
        }
    }
    public function portfolio(Request $request){
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='3'");
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='3'
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available artwork for sale",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function browseToken(Request $request){
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3'");
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3'
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available artwork for sale",
                ]
            ];
        }
        return response()->json($response, 200);
    }
}