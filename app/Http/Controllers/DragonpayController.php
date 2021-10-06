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
use App\PayoutTransaction;
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

        if ($transaction->transaction_type == Constants::TRANSACTION_TRANSFER) {
            $is_ts_owner = Edition::where('owner_id', $request->owner_id)->where('edition_id', $transaction->edition_id)->first();
            $has_transaction_success = Transaction::where('transaction_token_id', $request->input('transaction_token_id'))
                ->where('edition_id', $transaction->edition_id)
                ->whereNotNull('transaction_payment_tnxid')
                ->where('transaction_id', '<>', $request->input('transaction_id'))
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->whereNotIn('transaction_status', [Constants::TRANSACTION_FAILED, Constants::TRANSACTION_SUCCESS])
                ->first();

            if (!$is_ts_owner || $has_transaction_success) {
                $response = (object)["message" => "This artwork is already sold. Try other artworks."];
                return response()->json($response, 500);
            }
        }

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

    public function payout($payout_details, $transaction_details)
    {

        $url = $this->getBaseUrl() . 'DragonpayWebService/PayoutService.asmx';

        $headers = array();
        $headers[] = "Authorization: Bearer " . base64_encode(Self::MERCHANT_API_KEY);
        $headers[] = "Content-Type: application/json";

        $payout_amount = 0;

        if ($transaction_details) {
            $payout_amount = $transaction_details->transaction_grand_total - $transaction_details->transaction_computed_commission - $transaction_details->transaction_royalty_amount;

            $pout_tnx = PayoutTransaction::create([
                'user_id' => $payout_details->user_id,
                'amount' => $payout_amount,
                'status' => Constants::PAYOUT_STATUS_PENDING,
            ]);

            $data = [
                "TxnId" => $pout_tnx->id,
                "FirstName" => $payout_details->payout_first_name,
                "MiddleName" => $payout_details->payout_middle_name,
                "LastName" => $payout_details->payout_last_name,
                "Amount" => $payout_amount,
                "Currency" => "PHP",
                "Description" => "Payout",
                "ProcId" => $payout_details->payout_proc_id,
                "ProcDetail" => $payout_details->payout_proc_details,
                "RunDate" => date('Y-m-d '),
                "Email" => $payout_details->payout_email_address,
                "MobileNo" => $payout_details->payout_mobile_no,
                "BirthDate" => $payout_details->payout_birth_date,
                "Nationality" => "Philippines",
                "Address" => [
                    "Street1" => $payout_details->payout_street1,
                    "Street2" => $payout_details->payout_street2,
                    "Barangay" => $payout_details->payout_barangay,
                    "City" => $payout_details->payout_city,
                    "Province" => $payout_details->payout_province,
                    "Country" => $payout_details->payout_country
                ]
            ];

            $url = $this->getBaseUrl() . 'PayoutService.asmx?' . http_build_query($data, '', '&');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $output = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $return = [
                'body' => json_decode($output, true),
                'http_code' => $http_code
            ];
            curl_close($ch);

            return $return;
        }
    }

    private function header($xml, $asmx)
    {
        return array(
            "POST /DragonPayWebService/" . $asmx . " HTTP/1.1",
            "Host: " . $this->getBaseUrl(),
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml)
        );
    }

    private function run($xml, $headers, $asmx)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getBaseUrl() . 'DragonPayWebService/' . $asmx);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $response1 = str_replace("<soap:Body>", "", $response);
        $response2 = str_replace("</soap:Body>", "", $response1);

        $parser = simplexml_load_string($response2);
        $parser = json_encode($parser);
        $parser = json_decode($parser, true);

        return $parser;
    }
}
