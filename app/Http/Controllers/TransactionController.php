<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use App\User;
use App\User_profile;
use App\Wallet;
use App\Transaction;
use App\Constants;
use DB;

class TransactionController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function transferOwnership(Request $request)
    {
        $transaction = new Transaction();
        $searchTerm = $request->search_keyword;

        $transaction = $transaction->select(
                                'transactions.*', 
                                'tokens.*', 
                                'owner.user_profile_full_name as owner_fullname', 
                                'collector.user_profile_full_name as collector_fullname'
                    )
                    ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                    ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')             
                    ->join('user_profiles as owner', 'tokens.token_owner', 'owner.user_id')
                    ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                    ->where(function ($q) use ($searchTerm, $request) {
                        if ($searchTerm) {
                            $q->where('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%')
                            ->orWhere('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%');
                        }
                        if ($request->filter_status !== "") {
                            $q->where('transaction_payment_status', $request->filter_status);
                        }
                    })
                    ->orderBy($request->sort, $request->sort_dirc)
                    ->paginate($request->limit);
        
        $response = (object)[
            "success" => true,
            "result" => [
                "datas" => $transaction,
            ]
        ];
        return response()->json($response, 200);
    }

}
