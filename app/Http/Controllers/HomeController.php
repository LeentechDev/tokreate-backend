<?php

namespace App\Http\Controllers;

use App\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use  App\Token;
use DB;

class HomeController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    public function getTokens(Request $request)
    {
        $tokens = new Token();
        $searchTerm = $request->search_key;
        if ($searchTerm) {
            $tokens = $tokens->where('token_title', 'like', '%' . $searchTerm . '%')->orWhere('token_description', 'like', '%' . $searchTerm . '%');
        }

        $tokens = $tokens->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])->orderBy('token_id', 'DESC')->whereIn('token_status', [Constants::READY])->paginate($request->limit);

        foreach ($tokens as $key => $value) {
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
        }

        if ($tokens) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $tokens
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "No artworks found.",
                ]
            ];
            return response()->json($response, 409);
        }
    }

    public function specificToken($id)
    {

        $token_details = Token::find($id);
        if ($token_details) {
            /* $token_details['owner'] = $token_details->owner;
            $token_details['creator'] = $token_details->creator; */
            $token_details->transactions = $token_details->transactions()->orderBy('transaction_id', 'DESC')->get();
            $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));


            if (!$token_details->owner->profile->user_profile_avatar) {
                $token_details->owner->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
            if (!$token_details->creator->profile->user_profile_avatar) {
                $token_details->creator->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $token_details,
                    "message" => "Here are the details of the token.",
                ]
            ];
            return response()->json($response, 200);
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
}
