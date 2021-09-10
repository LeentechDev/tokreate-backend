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
use App\Edition;
use App\SiteSettings;
use App\TokenHistory;
use DB;

class DragonpayController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    protected const MERCHANT_ID = 'LEENTECH';
    protected const MERCHANT_PASS = 'Da5qgHfEw3zN';
    protected const MERCHANT_API_KEY = 'bec973b72e20e653ddc54c0b37cbf18a254b6928';
    protected const MODE = 'development';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function payment(Request $request)
    {
        $transaction = Transaction::find($request->input('transaction_id'));
        $allowance_fee = SiteSettings::where('name', 'allowance_fee')->first();
        if ($transaction) {
            $allowance_percentage = $allowance_fee->value;

            $tnxid = $request->input('transaction_token_id') . "-" . $request->input('transaction_id') . "-" . strtotime('now');
            $request['transaction_payment_tnxid'] = $tnxid;
            $request['transaction_allowance_fee'] = ($request->transaction_gas_fee * $allowance_percentage) / 100;

            $grand_total = 0;
            if ($transaction->transaction_type == Constants::TRANSACTION_MINTING) {
                $grand_total = $request->transaction_gas_fee + $request['transaction_allowance_fee'] + $transaction->transaction_computed_commission;
            }

            if ($transaction->transaction_type == Constants::TRANSACTION_TRANSFER) {

                if (!$transaction->edition_id) {
                    $token = Token::select('*', 'token_starting_price as current_price')->where('token_id', $transaction->transaction_token_id)->first();
                } else {
                    $token = Edition::select('*')->join('tokens', 'tokens.token_id', 'editions.token_id')->where('edition_id', $transaction->edition_id)->first();
                }

                $grand_total = $request->transaction_gas_fee + $request['transaction_allowance_fee'] + $token->current_price;
                $request['transaction_token_price'] = $token->current_price;
                $request['transaction_status'] = Constants::TRANSACTION_PENDING;
            }
            $request['transaction_grand_total'] = $grand_total;

            if ($transaction) {
                $transaction->update($request->all());

                $this->createHistory($transaction);
            }

            $user_details = User::find(Auth::user()->user_id);

            $params = array(
                'merchantid' => SELF::MERCHANT_ID,
                'txnid' => $tnxid,
                'amount' => $request['transaction_grand_total'],
                'ccy' => 'PHP',
                'description' => 'test',
                'email' => $user_details->user_email,

            );

            $params['amount'] = number_format($params['amount'], 2, '.', '');
            $params['key'] = SELF::MERCHANT_PASS;
            $digest_string = implode(':', $params);
            unset($params['key']);
            $params['digest'] = sha1($digest_string);
            if ($request->proc_id) {
                $params['procid'] = $request->proc_id;
            }

            $url = $this->getBaseUrl() . 'Pay.aspx?' . http_build_query($params, '', '&');


            $token_details = Token::find($transaction->transaction_token_id);
            if ($token_details) {
                $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));
            }
            $transaction['token_details'] = $token_details;

            $response = (object)[
                "result" => [
                    "url" =>  $url,
                    "token_id" => $request->input('transaction_token_id'),
                    "transaction" => $transaction,
                    "payment_method" => $request->input('transaction_payment_method')
                ]
            ];
            return response()->json($response, 200);
        } else {
            return response()->json(['message' => 'Invalid transaction.'], 500);
        }
    }

    public function createHistory($transaction)
    {

        $edition = Edition::find($transaction->edition_id);
        if ($edition) {
            TokenHistory::create([
                'token_id' => $transaction->transaction_token_id,
                'edition_id' => $transaction->edition_id,
                'price' => $edition->current_price,
                'type' => Constants::TOKEN_HISTORY_BUY,
                'buyer_id' => $transaction->user_id,
                'seller_id' => $edition->owner_id,
                'transaction_id' => $transaction->transaction_id
            ]);
        }
    }

    private function getBaseUrl()
    {
        if (SELF::MODE == 'development') {
            return 'https://test.dragonpay.ph/';
        } else {
            return 'https://gw.dragonpay.ph/';
        }
    }
}
