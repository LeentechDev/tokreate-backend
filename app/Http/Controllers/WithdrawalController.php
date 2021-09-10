<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Constants;
use App\Withdrawal;
use App\Transaction;
use DB; 
class WithdrawalController extends Controller
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

    public function requestWithdrawal(Request $request){
        $withdrawal = Withdrawal::create(
            [
                "withdrawal_user_id" =>  Auth::user()->user_id,
                "withdrawal_amount" => $request->input('amount'),
                "withdrawal_status" => Constants::WITHDRAWAL_REQUEST_STATUS,
                "withdrawal_gcash" => $request->input('phone'),
            ]
        );
        if ($withdrawal) {
            $response = (object)[
                "result" => [
                    "withdrawal" => $withdrawal,
                    "message" => "Your withdrawal has been successfully requested."
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function getWithdrawals(Request $request){
        $searchTerm = $request->search_keyword;
        try {
            $withdrawals = Withdrawal::join('user_profiles', 'user_profiles.user_id', 'withdrawals.withdrawal_user_id')
                    ->where(function ($q) use ($searchTerm, $request) {
                    if ($searchTerm) {
                        $q->where('user_profile_full_name', 'like', '%' . $searchTerm . '%');
                    }
                    if ($request->filter_status !== "") {
                        $q->where('withdrawal_status', $request->filter_status);
                    }
                    if($request->user_id){
                        $q->where('withdrawal_user_id', $request->user_id);
                    }
                })
                ->orderBy($request->sort, $request->sort_dirc)
                ->paginate($request->limit);

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $withdrawals,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json("Something wen't wrong", 500);
        }
    }

    public function getUserWithdrawals(Request $request){
        $searchTerm = $request->search_keyword;
        $user_id = Auth::user()->user_id;
        if($request->user_id){
            $user_id = $request->user_id;
        }
        
        // try {
            $withdrawals = Withdrawal::join('user_profiles', 'user_profiles.user_id', 'withdrawals.withdrawal_user_id')
                ->where(function ($q) use ($searchTerm, $request, $user_id) {
                        if ($searchTerm) {
                            $q->where('user_profile_full_name', 'like', '%' . $searchTerm . '%');
                        }
                        if ($request->filter_status !== "") {
                            $q->where('withdrawal_status', $request->filter_status);
                        }
                        $q->where('withdrawal_user_id', $user_id);
                })
                ->orderBy($request->sort, $request->sort_dirc)
                ->paginate($request->limit);

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $withdrawals,
                ]
            ];
            return response()->json($response, 200);
        // } catch (\Throwable $th) {
        //     return response()->json("Something wen't wrong", 500);
        // }
    }


    public function updateWithdrawalStatus(Request $request){
        $this->validate($request, [
            'withdrawal_id' => 'required|string',
            'withdrawal_status' => 'required|string',
        ]);
        $_withdrawal = Withdrawal::where('withdrawal_id', $request->input('withdrawal_id'));
        if ($_withdrawal) {
            $_withdrawal->update($request->all());
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Withdrawal status has been successfully updated."
                ]
            ];

            return response()->json($response, 200);
        } else {
            return response()->json(['message' => 'Transaction status update failed!'], 409);
        }
    }  


    public function getTotalEarnings(){
        
        $getTotalEarnings['totalEarnings'] = DB::table('transactions') 
        ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
        ->sum('transaction_computed_commission');

        $response = (object)[
            "success" => true,
            "result" => [
                "datas" => $getTotalEarnings,
            ]
        ];
        return response()->json($response, 200);
    }

    public function getCommissionList(Request $request){
        $searchTerm = $request->search_keyword;
        $getCommissionList =  Transaction::select(
            'transactions.*',
            'collector.user_profile_full_name as collector_fullname',
            'collector.user_profile_avatar as collector_avatar',
            'owner.user_profile_full_name as owner_fullname',
            'owner.user_profile_avatar as owner_avatar')

            ->whereNotNull('transactions.transaction_computed_commission')
            ->join('token_history', 'token_history.transaction_id', 'transactions.transaction_id')
            ->join('user_profiles as collector', 'collector.user_id', 'token_history.buyer_id')
            ->join('user_profiles  as owner', 'owner.user_id', 'token_history.seller_id')
            ->where('transaction_status', Constants::TRANSACTION_SUCCESS)

            ->where(function ($q) use ($searchTerm, $request) {
                if ($searchTerm) {
                    $q->where('transactions.transaction_id', 'like', '%' . $searchTerm . '%')
                        ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%');
                }
            })
            ->paginate($request->limit);

        if($getCommissionList){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $getCommissionList,  
                    "message" => "Here are the list of collected commissions",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    // "datas" => $getCommissionList, 
                    "message" => "There are no available collected commissions",
                ]
            ];
        }
        return response()->json($response, 200);
        


    }
}


