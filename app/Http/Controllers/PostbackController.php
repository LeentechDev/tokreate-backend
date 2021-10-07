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

class PostbackController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    protected const MERCHANT_ID = 'LEENTECH';
    protected const MERCHANT_PASS = 'Da5qgHfEw3zN';
    protected const MERCHANT_API_KEY = 'f275624ca412c6b00d4b11874d9ce38f020c9016';
    protected const MODE = 'development';


    public function webhook(Request $request)
    {

        $validateRequest = [
            $request->txnid,
            $request->refno,
            $request->status,
            $request->message,
            SELF::MERCHANT_PASS
        ];
        try {


            $validateDigest = sha1(implode(':', $validateRequest));


            if (strval($request->digest) == $validateDigest) {

                switch ($request->status) {
                    case 'S':
                        try {
                            Transaction::where('transaction_payment_tnxid', $request->txnid)->update([
                                'transaction_payment_status' => Constants::TRANSACTION_PAYMENT_SUCCESS
                            ]);
                            $transaction = Transaction::where('transaction_payment_tnxid', $request->txnid)->first();
                            // return responseWithMessage(200, "Success", $result);
                        } catch (\Throwable $th) {
                            return $th;
                        }
                        break;
                    case 'P':
                        try {
                            Transaction::where('transaction_payment_tnxid', $request->txnid)->update([
                                'transaction_payment_status' => Constants::TRANSACTION_PAYMENT_PENDING
                            ]);

                            $transaction = Transaction::where('transaction_payment_tnxid', $request->txnid)->first();
                            // return responseWithMessage(200, "Success", $result);
                        } catch (\Throwable $th) {
                            return $th;
                        }
                        break;
                    case 'F':
                        try {
                            Transaction::where('transaction_payment_tnxid', $request->txnid)->update([
                                'transaction_payment_status' => Constants::TRANSACTION_PAYMENT_FAILED
                            ]);
                            $transaction = Transaction::where('transaction_payment_tnxid', $request->txnid)->first();
                            // return responseWithMessage(200, "Success", $result);
                        } catch (\Throwable $th) {
                            return $th;
                        }
                        break;
                    case 'V':
                        try {
                            Transaction::where('transaction_payment_tnxid', $request->txnid)->update([
                                'transaction_payment_status' => Constants::TRANSACTION_PAYMENT_CANCEL
                            ]);
                            $transaction = Transaction::where('transaction_payment_tnxid', $request->txnid)->first();
                            // return responseWithMessage(200, "Success", $result);
                        } catch (\Throwable $th) {
                            return $th;
                        }
                        break;
                    default:
                        try {
                            Transaction::where('transaction_payment_tnxid', $request->txnid)->update([
                                'transaction_payment_status' => Constants::TRANSACTION_PAYMENT_FAILED
                            ]);
                            $result = Transaction::where('token_transaction_payment_tnxid', $request->txnid)->first();
                            // return responseWithMessage(200, "Success", $result);
                        } catch (\Throwable $th) {
                            return $th;
                        }
                        break;
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function webhookPayout(Request $request)
    {
        $request = $request->input();
        /* $isSafe = $this->_validateDigestSha1(
            $request['merchantTxnId'],
            $request['refNo'],
            $request['status'],
            $request['message'],
            $request['digest']
        ); */

        $file = 'test.txt';
        if (!is_file($file)) {         // Some simple example content.
            file_put_contents($file, 'asda');     // Save our content to the file.
        }

        /* if ($request['status'] == 'S' && $isSafe) {
        } */
    }

    private function _validateDigestSha1($merchanttxnid, $refNo, $status, $message, $digest)
    {
        $parameters = [
            $merchanttxnid,
            $refNo,
            $status,
            $message,
            $this->MERCHANT_PASS
        ];

        $digest_string = implode(':', $parameters);
        $sha1 = sha1($digest_string);
        return $sha1 == $digest;
    }


    private function getBaseUrl()
    {
        if (SELF::MODE == 'development') {
            return 'https://test.dragonpay.ph/';
        } else {
            return 'https://gw.dragonpay.ph/';
        }
    }

    private function header($xml)
    {
        return array(
            "POST /DragonpayWebService/PayoutService.asmx HTTP/1.1",
            "Host: " . $this->getBaseUrl(),
            "Content-Type: text/xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
            "SOAPAction: http://api.dragonpay.ph/RequestPayoutEx"
        );
    }

    private function run($xml, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getBaseUrl() . 'DragonPayWebService/PayoutService.asmx');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $response = curl_exec($ch);
        curl_close($ch);

        $response1 = str_replace("<soap:Body>", "", $response);
        $response2 = str_replace("</soap:Body>", "", $response1);

        $parser = simplexml_load_string($response2);
        $parser = json_encode($parser);
        $parser = json_decode($parser, true);

        return $parser;
    }


    public function payoutTest()
    {
        try {

            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">';
            $xml .= '<soap12:Body>';
            $xml .= '<RequestPayoutEx xmlns="http://api.dragonpay.ph/">';
            $xml .= '<apiKey>' . SELF::MERCHANT_API_KEY . '</apiKey>';
            $xml .= '<merchantTxnId>12345RTER</merchantTxnId>';
            $xml .= '<userName>Ribak</userName>';
            $xml .= '<amount>10000</amount>';
            $xml .= '<currency>PHP</currency>';
            $xml .= '<description>Payout</description>';
            $xml .= '<procId>GCSH</procId>';
            $xml .= '<procDetail>09069244734</procDetail>';
            $xml .= '<runDate>' . \Carbon\Carbon::now()->format('Y-m-d') . '</runDate>';
            $xml .= '<email>RonaldComendador20@gmail.com</email>';
            $xml .= '<mobileNo>09069244734</mobileNo>';
            $xml .= '</RequestPayoutEx>';
            $xml .= '</soap12:Body>';
            $xml .= '</soap12:Envelope>';

            $headers = $this->header($xml);
            $parser = $this->run($xml, $headers);

            return $parser;
        } catch (\Throwable $th) {
            return response()->json(['message' => "Unable to process payout, user don't payout details"], 409);
        }
    }
}
