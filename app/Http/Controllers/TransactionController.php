<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use App\User;
use App\User_profile;
use App\Wallet;
use App\Transaction;
use App\Token;
use App\Constants;
use App\Withdrawal;
use App\Notifications;
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

    public function transferOwnership(Request $request){
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

    public function withdrawalList(Request $request){
        $searchTerm = $request->search_keyword;
        $withdrawalList = DB::table('withdrawal')
            ->leftJoin('fund', 'withdrawal.w_fund_id', 'fund.fund_id')
            ->leftJoin('user_profiles as owner', 'fund.user_id', 'owner.user_id')
            ->orderBy('w_id', 'ASC')
            ->where(function ($q) use ($searchTerm, $request) {
                if ($searchTerm) {
                    $q->where('user_profile_full_name', 'like', '%' . $searchTerm . '%');
                }
            })
            ->orderBy($request->sort, $request->sort_dirc)
            ->paginate($request->limit);

        if($withdrawalList->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $withdrawalList,
                    "message" => "Here are the list of withdrawal",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available withdrawal",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function transactionReports(Request $request){
        try{
            // $reports['stats']['total_deposits'] = Transaction::where('transaction_type', Constants::TRANSACTION_TRANSFER)->count();
            $reports['stats']['total_purchase'] = Transaction::where('transaction_payment_status', Constants::TRANSACTION_PAYMENT_SUCCESS)->count();
            $reports['stats']['total_completed'] = Transaction::where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS )
                ->where('transaction_payment_status', Constants::TRANSACTION_PAYMENT_SUCCESS)->count();
            $reports['stats']['total_withdrawal'] = Withdrawal::count();

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $reports,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function purchaseList(Request $request){
        $purchaseList = new Transaction();
        $searchTerm = $request->search_keyword;
        $purchaseList = $purchaseList->select(
            'transactions.*',
            'tokens.*',
            'owner.user_profile_full_name as owner_fullname',
            'collector.user_profile_full_name as collector_fullname',
        )

        ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
        ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
        ->join('user_profiles as owner', 'tokens.token_owner', 'owner.user_id')

        ->where('transaction_payment_status', Constants::TRANSACTION_PAYMENT_SUCCESS)
        ->where(function ($q) use ($searchTerm, $request) {
            if ($searchTerm) {
                $q->where('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%');
            }
        })
        ->with(['transaction_owner', 'token'])
        ->orderBy($request->sort, $request->sort_dirc)
        ->paginate($request->limit);

        if($purchaseList->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $purchaseList,
                    "message" => "Here are the list of purchase token",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available purchase token",
                ]
            ];
        }
        return response()->json($response, 200);
    }
    
    public function updateTransactionStatus(Request $request){
        $this->validate($request, [
            'token_id' => 'required|string',
            'transaction_status' => 'required|string',
        ]);
        // try {
            $transaction = Transaction::where('transaction_token_id', $request->input('token_id'))->where('transaction_id', $request->input('transaction_id'));
            if($transaction){
                unset($request['token_id']);
                $transaction->update($request->all());
                $response=(object)[
                    "success" => true,
                    "result" => [
                        "message" => "Transaction status has been successfully updated."
                    ]
                ];
                $transaction = $transaction->first();
                $user_details = User::where('user_id', $transaction->user_id)->first();
                $msg = "";
                

                /* email and notification */
                switch ($request->transaction_status) {
                    case 1:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your purchase for "<b>'.$transaction->token->token_title.'</b>" is now processing.</p>';
                        break;
                    case 2:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your purchase for "<b>'.$transaction->token->token_title.'</b>" is failed.</p>';
                        break;
                    case 3:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your purchase for "<b>'.$transaction->token->token_title.'</b>" is successfull and transfered the ownership to you wallet.</p>';
                        break;
                    default:
                        # code...
                        break;
                }

                if($user_details->profile->user_mail_notification == 1){
                    Mail::send('mail.transfer-status', [ 'msg' => $msg], function($message) use ( $user_details) {
                        $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Purshase Token Status');
                        $message->from('support@tokreate.com','Tokreate');
                    });
                }

                if($user_details->profile->user_notification_settings == 1){
                    Notifications::create([
                        'notification_message' => $msg,
                        'notification_to' => $user_details->user_id,
                        'notification_from' => Auth::user()->user_id,
                        'notification_type' => Constants::NOTIF_MINTING_RES,
                    ]);
                }

                return response()->json($response, 200);
            }else{
                return response()->json(['message' => 'Transaction status update failed!'], 409);
            }
        /* }catch (\Exception $e) {
            return response()->json(['message' => 'Token status update failed!'], 409);
        } */
    }

}
