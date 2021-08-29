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
use App\SiteSettings;
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
        try {
            $transactions = $transaction->select(
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
                ->with(['transaction_owner', 'token'])
                ->orderBy($request->sort, $request->sort_dirc)
                ->paginate($request->limit);

            $response = (object)[
                    "success" => true,
                    "result" => [
                    "datas" => $transactions,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json("Something wen't wrong", 500);
        }
       
    }
    
    public function transactionDetails($id){
        /* try {  */
            $transaction = Transaction::with(['transaction_owner', 'token'])->find($id);
                if($transaction){
                    $response = (object)[
                        "success" => true,
                        "result" => [
                            "datas" => $transaction,
                        ]
                ];
            }else{
                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Can't find the transaction",
                    ]
                ];
            }
            return response()->json($response, 200);
        /* } catch (\Throwable $th) {
            return response()->json(["message" => "Something wen't wrong"], 500);
        } */
    }

    public function requestTransferOwnership(Request $request){
        $commission = SiteSettings::where('name', 'commission_percentage')->first();
        $transaction = Transaction::create(
            [
                "user_id" =>  Auth::user()->user_id,
                "transaction_token_id" => $request->input('token_id'),
                "transaction_type" => Constants::TRANSACTION_TRANSFER,
                "transaction_payment_method" =>  "",
                "transaction_details" =>  "",
                "transaction_service_fee" =>  0,
                "transaction_urgency"   => "",
                "transaction_gas_fee" =>  0,
                "transaction_allowance_fee" =>  0,
                "transaction_grand_total" => 0,
                "transaction_payment_status" => Constants::TRANSACTION_PAYMENT_PENDING,
                "transaction_status" =>  0,
                "transaction_computed_commission" => ($request->input('token_starting_price') * $commission->value) / 100,
            ]
        );    
        if($transaction){
            $response=(object)[
                "result" => [
                    "token" => $request->input('token_id'),
                    "transaction" => $transaction,
                    // "message" => "Your artwork has been successfully request for minting."
                ]
            ];
        }   
        return response()->json($response, 200);
    }

}
