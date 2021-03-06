<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use App\User;
use App\Token;
use App\Transaction;
use App\Constants;
use App\Edition;
use App\Fund;
use App\FundHistory;
use App\Notifications;
use App\Payout;
use App\Http\Controllers\DragonpayController;
use App\PayoutTransaction;

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

        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'tokens.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'token_history.buyer_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', '<>', Constants::TRANSACTION_DRAFT)
                ->where('transaction_status', '<>', Constants::TRANSACTION_SUCCESS)
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
                
                ->orderBy('transaction_payment_status', 'DESC')
                ->orderBy('transaction_status', 'ASC')
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

    public function transactionDetails($id)
    {
        try {
            $transaction = Transaction::with(['transaction_owner', 'token_history'])->find($id);

            if ($transaction) {

                if (!$transaction->edition_id) {
                    $transaction->token = Token::select('*', 'token_starting_price as current_price')->where('token_id', $transaction->transaction_token_id)->first();
                } else {
                    $transaction->token = Edition::select('*')->join('tokens', 'tokens.token_id', 'editions.token_id')->where('edition_id', $transaction->edition_id)->first();
                }

                $response = (object)[
                    "success" => true,
                    "result" => [
                        "datas" => $transaction,
                    ]
                ];
            } else {
                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Can't find the transaction",
                    ]
                ];
            }
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Something wen't wrong"], 500);
        }
    }

    public function requestTransferOwnership(Request $request)
    {

        $is_ts_owner = Edition::where('owner_id', $request->owner_id)->where('edition_id', $request->input('edition_id'))->first();
        if ($is_ts_owner) {
            $has_transaction_success = Transaction::where('transaction_token_id', $request->input('token_id'))
                ->where('edition_id', $request->input('edition_id'))
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->whereNotIn('transaction_status', [Constants::TRANSACTION_FAILED, Constants::TRANSACTION_SUCCESS])
                ->whereNotNull('transaction_payment_tnxid')
                ->first();
            if (!$has_transaction_success) {
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
                        "transaction_status" =>  Constants::TRANSACTION_DRAFT,
                        "edition_id" => $request->input('edition_id'),
                        /* "transaction_computed_commission" => ($request->input('token_starting_price') * $commission->value) / 100, */ /* no commission for every sales yet */
                        "transaction_computed_commission" => 0,
                    ]
                );
                if ($transaction) {
                    $response = (object)[
                        "result" => [
                            "token" => $request->input('token_id'),
                            "transaction" => $transaction,
                            // "message" => "Your artwork has been successfully request for minting."
                        ]
                    ];
                }
                return response()->json($response, 200);
            } else {
                $response = (object)["message" => "This artwork is already sold. Try other artworks."];
            }
        } else {
            $response = (object)["message" => "This artwork is already sold. Try other artworks."];
        }
        return response()->json($response, 500);
    }

    public function updateTransactionStatus(Request $request)
    {
        $this->validate($request, [
            'token_id' => 'required|string',
            'transaction_status' => 'required|string',
        ]);
        try {
            $_transaction = Transaction::where('transaction_token_id', $request->input('token_id'))->where('transaction_id', $request->input('transaction_id'));
            $transaction = $_transaction->first();
            $edition_details = Edition::find($transaction->edition_id);
            $token_creator = Token::find($transaction->transaction_token_id);

            $edition_owner = User::where('user_id',  $edition_details->owner_id)->first();
            $user_details = User::where('user_id', $transaction->user_id)->first();

            if ($_transaction) {
                unset($request['token_id']);

                $email_msg = "";

                $response = (object)[
                    "success" => true,
                    "result" => [
                        "message" => "Transaction status has been successfully updated."
                    ]
                ];

                switch ($request->transaction_status) {
                    case 1:
                        $email_msg = '<p>Hi <b>' . $user_details->profile->user_profile_full_name . '</b>, your purchase for "<b>' . $transaction->token->token_title . '</b>" is now processing.</p>';
                        break;
                    case 2:
                        $email_msg = '<p>Hi <b>' . $user_details->profile->user_profile_full_name . '</b>, your purchase for "<b>' . $transaction->token->token_title . '</b>" is failed.</p>';
                        break;
                    case 3:
                        /* request for payout */
                        $payout_details = Payout::where('user_id', $edition_owner->user_id)->first();

                        if ($payout_details) {
                            $dragonpay = new DragonpayController();
                            $transaction_details = $_transaction->first();

                            if($transaction_details){
                                $estimated_royalty_amount = 0;
                                if ($token_creator->creator->user_id !== $edition_details->owner_id) {
                                    $estimated_royalty_amount = ($edition_details->current_price * $token_creator->token_royalty) / 100;
                                }

                                $payout_amount = $transaction_details->transaction_token_price - $transaction_details->transaction_computed_commission - $estimated_royalty_amount;

                                $payout_res = $dragonpay->payout($payout_details, $transaction_details->transaction_id, $payout_amount);

                                if ($payout_res) {

                                    if (isset($payout_res['RequestPayoutExResponse'])) {
                                        $payout_res = $payout_res['RequestPayoutExResponse']['RequestPayoutExResult'];
                                    } else {
                                        $payout_res = $payout_res['RequestCashPayoutResponse']['RequestCashPayoutResult'];
                                    }

                                    switch ($payout_res) {
                                        case 0:
                                            $this->transferTokenOwnership($transaction_details);

                                            if ($user_details) {
                                                /* email and notification */
                                                $email_msg = '<p>Hi <b>' . $user_details->profile->user_profile_full_name . '</b>, your purchase for "<b>' . $transaction->token->token_title . '</b>" is successfull and transferred the ownership to your wallet.</p>';

                                                PayoutTransaction::create([
                                                    'user_id' => $payout_details->user_id,
                                                    'amount' => $payout_amount,
                                                    'status' => Constants::PAYOUT_STATUS_PENDING,
                                                    'transaction_id' => $transaction_details->transaction_id
                                                ]);
                                            }

                                            if ($edition_owner->profile->user_mail_notification == 1) {
                                                $email_msg2 = '<p>Hi <b>' . $edition_owner->profile->user_profile_full_name . '</b>, your token "<b>' . $transaction->token->token_title . '</b>" has been sold to ' . $user_details->profile->user_profile_full_name . ' for <b>Php ' . $transaction->transaction_token_price . '</b> .</p>';
                                                Mail::send('mail.email', ['msg' => $email_msg2, 'title' => 'Token Purchase'], function ($message) use ($edition_owner) {
                                                    $message->to($edition_owner->user_email, $edition_owner->profile->user_profile_full_name)->subject('Token Purchase');
                                                    $message->from('support@tokreate.com', 'Tokreate');
                                                });
                                            }

                                            if ($edition_owner->profile->user_notification_settings == 1) {
                                                Notifications::create([
                                                    'notification_message' => $email_msg2,
                                                    'notification_to' => $edition_owner->user_id,
                                                    'notification_from' => Auth::user()->user_id,
                                                    'notification_type' => Constants::NOTIF_SOLD_TOKEN,
                                                ]);
                                            }

                                            if ($token_creator->creator->user_id !== $edition_owner->user_id) {

                                                $payout_details = Payout::where('user_id', $token_creator->creator->user_id)->first();
                                                $royalty_payout_tnxid = 'R-'.$transaction_details->transaction_id;

                                                $payout_res2 = $dragonpay->payout($payout_details, $royalty_payout_tnxid, $estimated_royalty_amount);

                                                if (isset($payout_res2['RequestPayoutExResponse'])) {
                                                    $payout_res2 = $payout_res2['RequestPayoutExResponse']['RequestPayoutExResult'];
                                                } else {
                                                    $payout_res2 = $payout_res2['RequestCashPayoutResponse']['RequestCashPayoutResult'];
                                                }
            
                                                if ($payout_res2 == 0) {
                                                    PayoutTransaction::create([
                                                        'user_id' => $payout_details->user_id,
                                                        'amount' => $payout_amount,
                                                        'status' => Constants::PAYOUT_STATUS_PENDING,
                                                        'transaction_id' => $royalty_payout_tnxid
                                                    ]);
                                                }

                                                $email_msg3 = '<p>Hi <b>' . $token_creator->creator->profile->user_profile_full_name . '</b>, your created artwork "<b>' . $transaction->token->token_title . '</b>" has been sold for <b>Php ' . $transaction->transaction_token_price . '</b>. You will received <b>Php ' . $estimated_royalty_amount . '</b> royalty amount.</p>';
                                                if ($token_creator->creator->profile->user_mail_notification == 1) {
                                                    Mail::send('mail.email', ['msg' => $email_msg3, 'title' => 'Token Purchase'], function ($message) use ($token_creator) {
                                                        $message->to($token_creator->creator->user_email, $token_creator->creator->profile->user_profile_full_name)->subject('Token Purchase');
                                                        $message->from('support@tokreate.com', 'Tokreate');
                                                    });
                                                }

                                                if ($token_creator->creator->profile->user_notification_settings == 1) {
                                                    Notifications::create([
                                                        'notification_message' => $email_msg3,
                                                        'notification_to' => $token_creator->creator->user_id,
                                                        'notification_from' => Auth::user()->user_id,
                                                        'notification_type' => Constants::NOTIF_SOLD_TOKEN,
                                                    ]);
                                                }
                                            }
                                            break;
                                        case -1:
                                            return response()->json(['message' => "Something went wrong, please try again later."], 409);
                                            break;
                                        case -4:
                                            return response()->json(['message' => "Unable to create a payout transaction"], 409);
                                            break;
                                        case -5:
                                            return response()->json(['message' => "Invalid payout account details"], 409);
                                            break;
                                        case -6:
                                            return response()->json(['message' => "Cannot accept a pre-dated run date"], 409);
                                            break;
                                        case -7:
                                            return response()->json(['message' => "Amount limited exceeded"], 409);
                                            break;
                                        case -8:
                                            return response()->json(['message' => "Similar transaction id already exists"], 409);
                                            break;
                                        case -9:
                                            return response()->json(['message' => "Server IP access is not allowed"], 409);
                                            break;
                                        case -10:
                                            return response()->json(['message' => "Payout account is blacklisted"], 409);
                                            break;
                                        case -11:
                                            return response()->json(['message' => "Payout account is not enrolled for bank"], 409);
                                            break;
                                        case -12:
                                            return response()->json(['message' => "Invalid API Key"], 409);
                                            break;
                                        default:
                                            # code...
                                            break;
                                    }
                                }
                            }
                        } else {
                            return response()->json(['message' => "Unable to process payout, user don't have payout details"], 409);
                        }
                        break;
                    default:
                        # code...
                        break;
                }

                if ($user_details->profile->user_mail_notification == 1) {
                    Mail::send('mail.transfer-status', ['msg' => $email_msg], function ($message) use ($user_details) {
                        $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Purchase Token Status');
                        $message->from('support@tokreate.com', 'Tokreate');
                    });
                }

                if ($user_details->profile->user_notification_settings == 1) {
                    Notifications::create([
                        'notification_message' => $email_msg,
                        'notification_to' => $user_details->user_id,
                        'notification_from' => Auth::user()->user_id,
                        'notification_type' => Constants::NOTIF_MINTING_RES,
                    ]);
                }

                $_transaction->update($request->all());

                return response()->json($response, 200);
            } else {
                return response()->json(['message' => 'Transaction status update failed!'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token status update failed!'], 409);
        }
    }

    private function createEdition($transaction)
    {

        $_token = Token::select('*', 'token_starting_price as current_price')->where('token_id', $transaction->transaction_token_id);
        $_transaction = Transaction::find($transaction->transaction_id);
        $_edition = Edition::find($transaction->edition_id);
        $token = $_token->first();


        /* if creator is not the seller of the token compute the royalties */
        if ((int)$token->user_id !== (int)$_edition->owner_id) {

            $user_royalty = ($_edition->current_price * $token->token_royalty) / 100;

            $_transaction->transaction_royalty_amount = $user_royalty;
            $transaction_saved = $_transaction->save();

            if ($transaction_saved) {

                $fund = Fund::where('user_id', $token->user_id)->first();
                FundHistory::create([
                    'type' => Constants::FUND_SOURCE_ROYALTY,
                    'amount' => $user_royalty,
                    'fund_id' => $fund->fund_id,
                ]);
            }
        } else {
            if ($token->remaining_token > 0) {

                $total_edition = Edition::where('token_id', $token->token_id)->count();

                $token->remaining_token = $token->remaining_token - 1;
                $token->save();

                /* create edition */
                $edition_save = Edition::create([
                    'token_id' => $token->token_id,
                    'owner_id' => $token->user_id,
                    'current_price' => $token->current_price,
                    'edition_no' => $total_edition + 1,
                    'on_market' => $token->token_on_market,
                ]);
            }
        }

        $new_transaction = Transaction::find($transaction->transaction_id);

        $fund2 = Fund::where('user_id', $_edition->owner_id)->first();
        $user_earning = $new_transaction->transaction_token_price - ($new_transaction->transaction_computed_commission + $new_transaction->transaction_royalty_amount);
        FundHistory::create([
            'type' => Constants::FUND_SOURCE_SOLD,
            'amount' => $user_earning,
            'fund_id' => $fund2->fund_id,
        ]);

        return true;
    }

    private function transferTokenOwnership($transaction)
    {
        $_edition = Edition::select('*')->where('edition_id', $transaction->edition_id);
        $edition = $_edition->first();

        if ($_edition) {

            if ($this->createEdition($transaction)) {
                /* change the owner of token edition */
                $_edition->update([
                    'owner_id' => $transaction->user_id,
                    'on_market' => 0,
                ]);
            }
        };
    }

    public function transactionList(Request $request)
    {
        $transaction = new Transaction();
        $searchTerm = $request->search_keyword;
        try {
            if ($request->type === 'minting') {
                $transactions = $transaction->select(
                    'transactions.*',
                    'tokens.*',
                    'owner.user_profile_full_name as owner_fullname',
                    'owner.user_profile_avatar as owner_avatar'
                )
                    ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                    ->join('user_profiles as owner', 'tokens.user_id', 'owner.user_id')
                    ->where(function ($q) use ($searchTerm, $request) {
                        if ($searchTerm) {
                            $q->where('tokens.token_title', 'like', '%' . $searchTerm . '%')
                                ->orWhere('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%');
                        }
                        if ($request->filter_status !== '') {
                            $q->where('token_status', $request->filter_status);
                        }
                        $q->where('transaction_type', Constants::TRANSACTION_MINTING);
                    })
                    ->with(['transaction_owner', 'token'])
                    ->orderBy($request->sort, $request->sort_dirc)
                    ->paginate($request->limit);
            } else {
                $transactions = $transaction->select(
                    'transactions.*',
                    'tokens.*',
                    'owner.user_profile_full_name as owner_fullname',
                    'owner.user_profile_avatar as owner_avatar',
                    'collector.user_profile_full_name as collector_fullname',
                    'collector.user_profile_avatar as collector_avatar'
                )
                    ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                    ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                    ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                    ->join('user_profiles as collector', 'token_history.buyer_id', 'collector.user_id')
                    ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                    ->where(function ($q) use ($searchTerm, $request) {
                        if ($searchTerm) {
                            $q->where('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%')
                                ->orWhere('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%');
                        }
                        if ($request->filter_status !== "") {
                            $q->where('transaction_status', $request->filter_status);
                        } else {
                            $q->where('transaction_status', '<>', Constants::TRANSACTION_DRAFT);
                        }
                    })
                    ->with(['transaction_owner', 'token'])
                    ->orderBy($request->sort, $request->sort_dirc)
                    ->paginate($request->limit);
            }


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


    public function getTotalEarnings()
    {

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

    public function getCommissionList(Request $request)
    {
        $searchTerm = $request->search_keyword;
        $getCommissionList =  Transaction::select(
            'transactions.*',
            'collector.user_profile_full_name as collector_fullname',
            'collector.user_profile_avatar as collector_avatar',
            'owner.user_profile_full_name as owner_fullname',
            'owner.user_profile_avatar as owner_avatar'
        )

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

        if ($getCommissionList) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $getCommissionList,
                    "message" => "Here are the list of collected commissions",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
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
