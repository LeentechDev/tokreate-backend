<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Data_policy;
use DB;

class DataPolicyController  extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */


    public function viewDataPolicy(){
        $dataPolicy= DB::select("SELECT * FROM `data_policy`
        LEFT JOIN `user_profiles` ON `user_profiles`.`user_id` = `data_policy`.`data_policy_update_by`
        WHERE id='1'");
       
        if($dataPolicy){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $dataPolicy,
                    "message" => "Here are the details of data policy.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Data Policy not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }


    public function updateDataPolicy(Request $request)
    {
        $dataPolicy = Data_policy::findOrFail($request->id);
        
        if($dataPolicy){
            $dataPolicy->update($request->all());
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Data Policy has been successfully updated",
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        } 
        return response()->json($response, 200);
    }
}


    
