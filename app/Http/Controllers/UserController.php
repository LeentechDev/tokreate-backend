<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\User;
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
        $user=DB::select("SELECT * FROM `users`
            LEFT JOIN `user_profiles` ON `users`.`user_id`=`user_profiles`.`user_id` 
            WHERE `users`.`user_id`='".Auth::user()->user_id."' and `users`.`user_role_id`='1'");
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $user,
                ]
            ];
        return response()->json($response, 200);
    }

    public function allUsers(){
        $user=DB::select("SELECT * FROM `users`
            LEFT JOIN `user_profiles` ON `users`.`user_id`=`user_profiles`.`user_id` 
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

            return response()->json(['message' => 'user not found!'], 404);
        }

    }
    public function mintRequest(Request $request){
        $name = $request->input('user_id');
        dd($name);
    }
}
