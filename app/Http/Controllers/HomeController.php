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

    public function getTokens(Request $request){
        $tokens = new Token();
        $searchTerm = $request->search_key;
        if($searchTerm){
            $tokens = $tokens->where('token_title', 'like', '%' . $searchTerm. '%')->orWhere('token_description', 'like', '%' . $searchTerm. '%');
        }

        $tokens = $tokens->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])->orderBy('token_id','DESC')->paginate($request->limit);
        
        foreach ($tokens as $key => $value) {
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
        }

        if($tokens){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $tokens
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "No artworks found.",
                ]
            ];
            return response()->json($response, 409);
        }
    }

}