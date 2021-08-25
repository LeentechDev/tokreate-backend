<?php

namespace App\Http\Controllers;

use App\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use App\Token;
use App\Transaction;
use App\SiteSettings;
use DB;

class DashboardController extends Controller
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

    public function dashboardReports(Request $request)
    {
        // try {
        $reports['stats']['total_transaction'] = Transaction::where('transaction_type', Constants::TRANSACTION_TRANSFER)->count();
        $reports['stats']['total_offers'] = Transaction::where('transaction_type', Constants::TRANSACTION_TRANSFER)->count();
        $reports['stats']['total_users'] = User::where('user_role_id', Constants::USER_ARTIST)->count();
        $reports['stats']['total_tokens'] = Token::count();

        /* var_dump(Constants::TRANSACTION_TRANSFER);
            var_dump(Constants::TRANSACTION_SUCCESS); */
        $reports['top_collector'] = User::select('*', DB::raw("sum(transactions.transaction_token_price) as totalTransaction"))
            ->join('transactions', 'transactions.user_id', 'users.user_id')
            ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
            ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
            ->where('users.user_role_id', Constants::USER_ARTIST)
            ->groupBy('users.user_id')
            ->orderBy('totalTransaction', 'ASC')
            ->paginate(10);

        $response = (object)[
            "success" => true,
            "result" => [
                "datas" => $reports,
            ]
        ];
        return response()->json($response, 200);
        // } catch (\Throwable $th) {
        //     return response()->json(['message' => "Something wen't wrong"], 200);
        // }
    }
}
