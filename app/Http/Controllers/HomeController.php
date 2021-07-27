<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use  App\Token;
use DB;

class HomeController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    public function browseToken(Request $request){
        $search="";
        if($request->has('search_keyword')){
            $search=" AND (`user_profiles`.`user_profile_full_name` LIKE '%".$request->search_keyword."%'
            OR `tokens`.`token_title` LIKE '%".$request->search_keyword."%')";
        }
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3' ".$search);
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3' ".$search."
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit,
                    "message" => "Here are the list of token available for sale",
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
    public function homeToken(){
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`token_status`='3' ORDER BY `tokens`.`token_id` DESC LIMIT 12");
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "message" => "Here are the list of token available for sale",
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

    public function specificToken($id){
        $token_details= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`token_id`='".$id."'");
        if($token_details){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_details,
                    "message" => "Here are the details of the token.",
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "Token not found.",
                ]
            ];
        }
        return response()->json($response, 200);
    }
}