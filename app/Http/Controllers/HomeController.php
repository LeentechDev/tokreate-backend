<?php

namespace App\Http\Controllers;

use App\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use App\Token;
use App\Gas_fee;
use App\SiteSettings;
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
        $searchTerm = $request->search_key;

        $tokens = Token::rightJoin('editions', 'editions.token_id', 'tokens.token_id')->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])
            ->orderBy('tokens.token_id', 'DESC')
            ->whereIn('token_status', [Constants::READY])
            ->where('editions.on_market', Constants::TOKEN_ON_MARKET)
            ->where(function ($q) use ($searchTerm) {
                if ($searchTerm) {
                    $q->where('token_title', 'like', '%' . $searchTerm . '%')->orWhere('token_description', 'like', '%' . $searchTerm . '%');
                }
            })
            ->paginate($request->limit);

        foreach ($tokens as $key => $value) {
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
            $tokens[$key]->owner = User::find($value->owner_id);
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

    public function specificToken(Request $req)
    {

        $token_details = Token::find($req->token_id);
        if ($token_details) {

            /* if the given user is not the creator of token get the details from token table along with edition details */
            if ($req->edition_id) {
                $token_details = Token::where('edition_id', $req->edition_id)
                    ->join('editions', 'editions.token_id', 'tokens.token_id')
                    ->first();
                $token_details->owner = User::find($token_details->owner_id);
            } else {
                return response()->json(['message' => 'Invalid Token'], 500);
            }

            if ($token_details) {
                $token_details->transactions = $token_details->transactions()->orderBy('transaction_id', 'DESC')->get();
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

    public function getGasFees()
    {
        $config = DB::select('SELECT * FROM `gas_fees`');

        if ($gas_fees) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $gas_fees,
                    "message" => "Here are the list of gas fees",
                ]
            ];
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available gas fees",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function updateSiteSettings(Request $r)
    {
        try {
            DB::table('gas_fees')->where('gas_fee_name', 'slow')->update(['gas_fee_amount' => $r->slow]);
            DB::table('gas_fees')->where('gas_fee_name', 'medium')->update(['gas_fee_amount' => $r->medium]);
            DB::table('gas_fees')->where('gas_fee_name', 'fast')->update(['gas_fee_amount' => $r->fast]);
            DB::table('gas_fees')->where('gas_fee_name', 'superfast')->update(['gas_fee_amount' => $r->superfast]);
            SiteSettings::where('name', 'commission_percentage')->update(['value' => $r->commision_rate]);
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Successfully update rates",
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Unable to update '], 200);
        }
    }

    public function siteSettings()
    {
        $allowance = SiteSettings::where('name', 'allowance_fee')->first();
        $commission_rate = SiteSettings::where('name', 'commission_percentage')->first();
        $gas_fees = DB::select("SELECT * FROM `gas_fees` join `user_profiles` on `user_profiles`.`user_id` = `gas_fees`.`gas_fee_updated_by`");

        if ($gas_fees) {
            $config['allowance_fee'] = $allowance;
            $config['commission_rate'] = $commission_rate;
            $config['gas_fees'] = $gas_fees;

            $response = (object)[
                "success" => false,
                "result" => [
                    "datas" => $config,
                ]
            ];
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available gas fees",
                ]
            ];
        }

        return response()->json($response, 200);
    }

    
}
