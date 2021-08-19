<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Gas_fee;
use App\User_profile;
use DB;

class GasFeeController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth');
    }


    public function viewGasfee(){ 
        $gas_fee = DB::table('gas_fees')
            ->leftJoin('user_profiles', 'gas_fees.gas_fee_id', '=', 'user_profiles.user_id')
            ->get();

        if($gas_fee){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $gas_fee,
                    "message" => "Here are the list of gas fee",
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available gas fee",
                ]
            ];
        }
        return response()->json($response, 200);
    }


    public function updateGasFee(Request $request){
        // slow
        Gas_fee::where('gas_fee_id', '1')->update(array('gas_fee_amount' => $request->gas_fee_amount0));

        // medium
        Gas_fee::where('gas_fee_id', '2')->update(array('gas_fee_amount' => $request->gas_fee_amount1));

        // fast
        Gas_fee::where('gas_fee_id', '3')->update(array('gas_fee_amount' => $request->gas_fee_amount4));

        // superfast
        Gas_fee::where('gas_fee_id', '4')->update(array('gas_fee_amount' => $request->gas_fee_amount2));

        // commission rate
        Gas_fee::where('gas_fee_id', '5')->update(array('gas_fee_amount' => $request->gas_fee_amount3));
       

        $response=(object)[
            "success" => true,
            "result" => [
                "message" => "Gas fee has been successfully updated",
            ]
        ];

        return response()->json($response, 200);
    }
}


    
