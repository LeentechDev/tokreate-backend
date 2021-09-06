<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Constants;
use App\Withdrawal;

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
                "withdrawal_amount" => $request->input('withdrawal_amount'),
                "withdrawal_status" => Constants::WITHDRAWAL_REQUEST_STATUS,
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
}
