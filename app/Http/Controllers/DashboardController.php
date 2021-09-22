<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Edition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use App\Token;
use App\Transaction;
use App\SiteSettings;
use App\TokenHistory;
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
        try {
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
                ->groupBy('transactions.user_id')
                ->orderBy('totalTransaction', 'DESC')
                ->paginate(10);

            $reports['top_artists'] = User::select('*', DB::raw("sum(fund_history.amount) as totalSales "))
                ->join('fund', 'fund.user_id', 'users.user_id')
                ->join('fund_history', 'fund.fund_id', 'fund_history.fund_id')
                ->where('users.user_role_id', Constants::USER_ARTIST)
                ->groupBy('fund_history.fund_id')
                ->orderBy('totalSales', 'DESC')
                ->paginate(10);

            $reports['top_tokens'] = Token::select('tokens.*', DB::raw("max(token_history.price) as averageSale "), DB::raw("count(token_history.id) as salesNo"))
                ->join('editions', 'editions.token_id', 'tokens.token_id')
                ->join('token_history', 'editions.edition_id', 'token_history.edition_id')
                ->where('token_history.type', Constants::TOKEN_HISTORY_BUY)
                ->groupBy('token_history.token_id')
                ->orderBy('averageSale', 'DESC')
                ->paginate(10);

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

    public function pendingTransactions(Request $request)
    {
        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_PENDING)
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

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $transactions,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }


    public function successTransactions(Request $request)
    {
        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
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

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $transactions,
                ]
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function userSales(Request $request)
    {
        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->where(function ($q) use ($searchTerm, $request) {
                    if ($searchTerm) {
                        $q->where('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%');
                    }
                })
                ->with(['transaction_owner', 'token'])
                ->where('token_history.seller_id', Auth::user()->user_id)
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
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function userPurchase(Request $request)
    {

        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->where(function ($q) use ($searchTerm, $request) {
                    if ($searchTerm) {
                        $q->where('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%');
                    }
                })
                ->with(['transaction_owner', 'token'])
                ->where('transactions.user_id', Auth::user()->user_id)
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
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function userIncoming(Request $request)
    {
        $searchTerm = $request->search_keyword;
        // try {
        $transactions = Transaction::select(
            'transactions.*',
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
            ->where('transaction_status', '<>', Constants::TRANSACTION_SUCCESS)
            ->where(function ($q) use ($searchTerm, $request) {
                if ($searchTerm) {
                    $q->where('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%');
                }
            })
            ->with(['transaction_owner', 'token'])
            ->where('token_history.seller_id', Auth::user()->user_id)
            ->orderBy($request->sort, $request->sort_dirc)
            ->paginate($request->limit);

        $response = (object)[
            "success" => true,
            "result" => [
                "datas" => $transactions,
            ]
        ];
        return response()->json($response, 200);
        // } catch (\Throwable $th) {
        //     return response()->json(['message' => "Something wen't wrong"], 200);
        // }
    }

    public function userOutgoing(Request $request)
    {

        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
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
                ->where('transaction_status', '<>', Constants::TRANSACTION_SUCCESS)
                ->where(function ($q) use ($searchTerm, $request) {
                    if ($searchTerm) {
                        $q->where('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%');
                    }
                })
                ->with(['transaction_owner', 'token'])
                ->where('transactions.user_id', Auth::user()->user_id)
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
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function userRoyalties(Request $request)
    {

        $searchTerm = $request->search_keyword;
        try {
            $transactions = Transaction::select(
                'transactions.*',
                'collector.user_profile_full_name as collector_fullname',
                'collector.user_profile_avatar as collector_avatar',
                'owner.user_profile_full_name as owner_fullname',
                'owner.user_profile_avatar as owner_avatar'
            )
                ->join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->whereNotNull('transaction_royalty_amount')
                ->where(function ($q) use ($searchTerm, $request) {
                    if ($searchTerm) {
                        $q->where('owner.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('collector.user_profile_full_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('tokens.token_title', 'like', '%' . $searchTerm . '%');
                    }
                })
                ->with(['transaction_owner', 'token'])
                ->where('tokens.user_id', Auth::user()->user_id)
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
            return response()->json(['message' => "Something wen't wrong"], 200);
        }
    }

    public function userDashboardReports(Request $request)
    {
        try {
            $reports['stats']['total_sales'] = TokenHistory::join('transactions', 'token_history.transaction_id', 'transactions.transaction_id')
                ->where('token_history.seller_id', Auth::user()->user_id)
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->sum("price");

            $reports['stats']['total_purchase'] = TokenHistory::join('transactions', 'token_history.transaction_id', 'transactions.transaction_id')
                ->where('token_history.buyer_id', Auth::user()->user_id)
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->sum("price");
            $reports['stats']['total_tokens'] = Edition::where('owner_id', Auth::user()->user_id)->count();

            $reports['stats']['total_royalty_earnings'] = Transaction::join('token_history', 'transactions.transaction_id', 'token_history.transaction_id')
                ->join('user_profiles as collector', 'transactions.user_id', 'collector.user_id')
                ->join('user_profiles as owner', 'token_history.seller_id', 'owner.user_id')
                ->join('tokens', 'tokens.token_id', 'transactions.transaction_token_id')
                ->where('transaction_type', Constants::TRANSACTION_TRANSFER)
                ->where('transaction_status', Constants::TRANSACTION_SUCCESS)
                ->whereNotNull('transaction_royalty_amount')
                ->where('tokens.user_id', Auth::user()->user_id)
                ->sum('transaction_royalty_amount');

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

    public function yearlyEarnings(Request $request){
        $year = $request->year;
        
            

            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $reports,
                ]
            ];

            return response()->json($response, 200);
        /* } catch (\Throwable $th) {
            return response()->json(['message' => "Something wen't wrong"], 200);
        } */
    }

    public function getTotalEarnings(Request $request){
        // try{
            $total_earnings['totalEarnings'] = DB::table('transactions') 
                ->where('transaction_payment_status', Constants::TRANSACTION_PAYMENT_SUCCESS)
                ->sum('transaction_computed_commission');
            $year = $request->year ? $request->year : date('Y');
            $datas = array();

            $res = DB::table('transactions')->select(
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 1 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as jan "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 2 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as feb "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 3 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as mar "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 4 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as apr "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 5 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as may "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 6 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as jun "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 7 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as jul "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 8 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as aug "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 9 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as sept "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 10 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as oct "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 11 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as nov "),
                DB::raw("(Select SUM(transaction_computed_commission) from transactions where EXTRACT(MONTH FROM transaction_created_at) = 12 and EXTRACT(YEAR FROM transaction_created_at) = ".$year." and transaction_status = ".Constants::TRANSACTION_SUCCESS.") as dece ")
            )->first();

            $total_earnings['monthly_earnings'] = $res;
            $total_earnings['year'] = $year;
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $total_earnings,
                ]
            ];
            return response()->json($response, 200);
        /* } catch (\Throwable $th) {
            return response()->json(['message' => "Something wen't wrong"], 200);
        } */
    }

    public function getCommissionList(Request $request){
        $searchTerm = $request->search_keyword;
        $getCommissionList =  Transaction::select(
            'transactions.*',
            'collector.user_profile_full_name as collector_fullname',
            'collector.user_profile_avatar as collector_avatar',
            'owner.user_profile_full_name as owner_fullname',
            'owner.user_profile_avatar as owner_avatar')
            ->join('token_history', 'token_history.transaction_id', 'transactions.transaction_id')
            ->join('user_profiles as collector', 'collector.user_id', 'token_history.buyer_id')
            ->join('user_profiles  as owner', 'owner.user_id', 'token_history.seller_id')
            ->where('transaction_payment_status', Constants::TRANSACTION_PAYMENT_SUCCESS)
            ->where('transactions.transaction_computed_commission', '<>', 0)
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
