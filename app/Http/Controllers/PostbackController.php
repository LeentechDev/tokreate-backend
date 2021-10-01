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
    protected const MERCHANT_API_KEY = 'bec973b72e20e653ddc54c0b37cbf18a254b6928';
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
}
