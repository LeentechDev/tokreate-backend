<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\User_profile;
use App\Token;
use App\Wallet;
use App\Transaction;
use App\Notifications;
use App\Constants;
use App\Edition;
use App\TokenHistory;
use App\SiteSettings;
use DB;
use Illuminate\Support\Facades\Mail;

class TokenController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    public function __construct(){
        $this->middleware('auth');
    }

    public function getTokens(Request $request){
        $tokens = new Token();
        $searchTerm = $request->search_key;

        $tokens = $tokens->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])
        ->orderBy('token_id','DESC')
        ->where(function ($q) use ($searchTerm) {
            if ($searchTerm) {
                $q->where('token_title', 'like', '%' . $searchTerm . '%')->orWhere('token_description', 'like', '%' . $searchTerm . '%');
            }
        })
        ->paginate($request->limit);
        
        foreach ($tokens as $key => $value) {
            $tokens[$key]->token_properties = json_decode(json_decode($value->token_properties));
        }

        if($tokens){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $tokens
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "No artworks found.",
                ]
            ];
            return response()->json($response, 409);
        }
    }

    public function specificToken($id){

        $token_details = Token::find($id);
        if($token_details){
            /* $token_details['owner'] = $token_details->owner;
            $token_details['creator'] = $token_details->creator; */
            $token_details->transactions = $token_details->transactions()->orderBy('transaction_id', 'DESC')->get();
            $token_details->mint_transactions = $token_details->transactions()->where('transaction_type', Constants::TRANSACTION_MINTING)->orderBy('transaction_id', 'ASC')->first();
            $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));

          
            /* if(!$token_details->owner->profile->user_profile_avatar){
                $token_details->owner->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
            if(!$token_details->creator->profile->user_profile_avatar){
                $token_details->creator->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            } */

            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_details,
                    "message" => "Here are the details of the token.",
                ]
            ];
            return response()->json($response, 200);
        }else{   
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "Token not found.",
                ]
            ];
            return response()->json($response, 409);
        }
    }

    public function addToMarket(Request $request){
        $tokens = Token::where('token_id',$request->token_id)->where('token_on_market', !Constants::TOKEN_ON_MARKET)->where('token_owner', Auth::user()->user_id)->first();
        if($tokens){
            $tokens->token_on_market = Constants::TOKEN_ON_MARKET;
            $tokens->token_starting_price = $request->price;
            $updated = $tokens->update();
            if($updated){
                $response=(object)[
                    "success" => true,  
                    "result" => [
                        "datas" => $updated,
                        "message" => "Artwork successfully put on marketplace",
                    ]
                ];
                return response()->json($response, 200);
            }
        }
        $response=(object)[
            "message" => "Artwork ".$request->token_id." not found in your collection.",
        ];
        return response()->json($response, 409);
    }

    public function mintRequest(Request $request){
        $this->validate($request, [
            /* 'user_id' => 'required|string', */
            'token_collectible' => 'required|integer',
            'token_collectible_count' => 'required|integer',
            'token_title' => 'required|string',
            /* 'token_description' => 'required|string', */
            'token_royalty' => 'required|int',
            'token_filename' => 'required',
            'token_filetype' => 'required',
            'token_saletype' => 'required|string',
            /* 'token_status' => 'required|string', */
        ]);
        function getRandomString($n) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            
            for ($i = 0; $i < $n; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
            
            return $randomString;
        }
        $wallet_details = Wallet::where('user_id', Auth::user()->user_id)->where('wallet_status', Constants::WALLET_DONE)->first();

        if($wallet_details){
            /* try { */
                $token = new Token;
                $token_file             = $request->file('token_filename');
                $token_original_name    = $token_file->getClientOriginalName();
                $token_extension        = $token_file->guessExtension();
                $token_new_file_name    = date('His').'-'.getRandomString(8);
                $generated_token        = $token_new_file_name.'.'.$token_extension;
                // destination path for production
                // $token_destination_path = storage_path('app/images/user_avatar');
                $token_destination_path = 'app/images/tokens';
                $token_file->move($token_destination_path, $generated_token);
                

                $token->user_id = Auth::user()->user_id;
                $token->token_collectible = $request->input('token_collectible');
                $token->token_collectible_count = $request->input('token_collectible_count');
                $token->token_title = $request->input('token_title');
                $token->token_description = $request->input('token_description');
                $token->token_starting_price = $request->input('token_starting_price');
                $token->token_royalty = $request->input('token_royalty');
                $token->token_properties = $request->input('token_properties') ? json_encode($request->input('token_properties')) : '' ;
                $token->token_file = url('app/images/tokens/'.$generated_token);
                $token->token_saletype = $request->input('token_saletype');
                $token->token_filetype = $request->input('token_filetype');
                $token->token_status = Constants::PENDING;
                $token->token_owner = Auth::user()->user_id;
                $token->token_creator = Auth::user()->user_id;
                $token->token_on_market = $request->input('put_on_market');
                $token->save();

                $token_id = $token->token_id;

                $commission = SiteSettings::where('name', 'commission_percentage')->first();

                $transaction = Transaction::create(
                    [
                        "user_id" =>  Auth::user()->user_id,
                        "transaction_token_id" => $token_id,
                        "transaction_type" => Constants::TRANSACTION_MINTING,
                        "transaction_payment_method" =>  "",
                        "transaction_details" =>  "",
                        "transaction_service_fee" =>  0,
                        "transaction_urgency"   => "",
                        "transaction_gas_fee" =>  0,
                        "transaction_allowance_fee" =>  0,
                        "transaction_grand_total" => 0,
                        "transaction_payment_status" => Constants::TRANSACTION_PAYMENT_PENDING,
                        "transaction_status" =>  0,
                        "transaction_computed_commission" => ($request->input('token_starting_price') * $commission->value) / 100,
                    ]
                );

                if($token){
                    $no_of_edition = $request->input('token_collectible_count');
                    for($i = 0; $no_of_edition > $i; $i++){
                        /* create new edition */
                        $edition = new Edition;
                        $edition->token_id = $token_id;
                        $edition->owner_id = Auth::user()->user_id;
                        $edition->current_price = $request->input('token_starting_price');
                        $edition->edition_no = $i+1;
                        $edition->save();
                        /* create token/history logs */
                        if($edition){
                            $history = new TokenHistory;
                            $history->token_id = $token_id;
                            $history->edition_id = $edition->edition_id;
                            $history->price = $request->input('token_starting_price');
                            $history->type = Constants::TOKEN_HISTORY_MINT;
                            $history = $history->save();
                        }
                    }

                    $user_details = User::find(Auth::user()->user_id);
                    
                    Notifications::create([
                        'notification_message' => '<p><b>'.$user_details->profile->user_profile_full_name.'</b> request for minting.</p>',
                        'notification_to' => 0,
                        'notification_from' => Auth::user()->user_id,
                        'notification_item' => $token_id,
                        'notification_type' => Constants::NOTIF_MINTING_REQ,
                    ]);
                }


                $token_details = Token::find($token_id);
                if($token_details){
                    $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));
                }
                $transaction['token_details'] = $token_details;

                $response=(object)[
                    "success" => true,
                    "result" => [
                        "token" => $token_id,
                        "transaction" => $transaction,
                        "message" => "Your artwork has been successfully request for minting."
                    ]
                ];
                return response()->json($response, 200);
            /* }catch (\Exception $e) {
                return response()->json(['message' => 'Request for Minting Failed!'], 409);
            } */
        }else{
            return response()->json(['message' => 'Please set up your wallet first'], 409);
        }
    }

    /* public function portfolio(Request $request){
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='3'");
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        LEFT JOIN `transactions` ON `transactions`.`transaction_token_id`=`tokens`.`token_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='3'
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit
                ]
            ];
            return response()->json($response, 200);
        }else{
            return response()->json(['message' => 'There are no available artwork for sale.'], 409);
        }
    } */

    /* public function browseToken(Request $request){
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3'");
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `token_status`='3'
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit
                ]
            ];
            return response()->json($response, 200);
        }else{
            return response()->json(['message' => 'There are no available artwork for sale.'], 409);
        }   
    } */

    public function updateTokenStatus(Request $request){
        $this->validate($request, [
            'token_id' => 'required|string',
            'token_status' => 'required|string',
        ]);
        try {
            $token_details = Token::where('token_id', $request->input('token_id'));
            if($token_details){
                $token_details->update($request->all());
                $response=(object)[
                    "success" => true,
                    "result" => [
                        "message" => "Token status has been successfully updated."
                    ]
                ];
                $token_details =$token_details->first();
                $user_details = User::where('user_id', $token_details->token_owner)->first();
                $msg = "";
                /* email and notification */
                switch ($request->token_status) {
                    case 1:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your artwork minting request for "<b>'.$token_details->token_title.'</b>" is now processing.</p>';
                        break;
                    case 2:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your artwork minting request for "<b>'.$token_details->token_title.'</b>" is failed.</p>';
                        break;
                    case 3:
                        $msg = '<p>Hi <b>'.$user_details->profile->user_profile_full_name.'</b>, your artwork "<b>'.$token_details->token_title.'</b>" is now ready.</p>';
                        break;
                    default:
                        # code...
                        break;
                }

                

                Mail::send('mail.minting-status', [ 'msg' => $msg], function($message) use ( $user_details) {
                    $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Minting Status Update');
                    $message->from('support@tokreate.com','Tokreate');
                });

                if($user_details->user_notification_settings == 1){
                    Notifications::create([
                        'notification_message' => $msg,
                        'notification_to' => $user_details->user_id,
                        'notification_from' => Auth::user()->user_id,
                        'notification_type' => Constants::NOTIF_MINTING_RES,
                    ]);
                }

                return response()->json($response, 200);
            }else{
                return response()->json(['message' => 'Token status update failed!'], 409);
            }
        }catch (\Exception $e) {
            return response()->json(['message' => 'Token status update failed!'], 409);
        }
    }

    public function mintingList(Request $request){
        $tokens = new Token();
        $searchTerm = $request->search_key;

        $token_list = $tokens
                        ->join('transactions','transactions.transaction_token_id','tokens.token_id')
                        ->with(['owner'])->orderBy('token_status', 'ASC')
                        ->where(function ($q) use ($searchTerm, $request) {
                            if ($searchTerm) {
                                $q->where('token_title', 'like', '%' . $searchTerm . '%')
                                ->orWhere('token_description', 'like', '%' . $searchTerm . '%');
                            }
                            if($request->filter_urgency !== ""){
                                $q->where('transaction_urgency', $request->filter_urgency);
                            }
                        })
                        ->where('transactions.transaction_type', Constants::TRANSACTION_MINTING)
                        ->paginate($request->limit);

        if($token_list->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "message" => "Here are the list of token",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,  
                "result" => [
                    "message" => "There are no available artwork for sale",
                ]
            ];
            return response()->json($response, 200);
        }    
    }

    /* public function collection(Request $request){
        $token= DB::select("SELECT COUNT(*) as total_token FROM tokens
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='5'");
        $total_token=$token[0]->{'total_token'};
        $total_pages=ceil($total_token / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $token_list= DB::select("SELECT * FROM `tokens` 
        LEFT JOIN `user_profiles` ON `tokens`.`user_id`=`user_profiles`.`user_id` 
        LEFT JOIN `transactions` ON `transactions`.`transaction_token_id`=`tokens`.`token_id` 
        WHERE `tokens`.`user_id`='".Auth::user()->user_id."' and `tokens`.`token_status`='5'
        LIMIT ".$offset.", ". $request->limit);
        if($token_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $token_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_token,
                    "limit" => $request->limit
                ]
            ];
            return response()->json($response, 200);
        }else{
            return response()->json(['message' => 'There are no available artwork for sale.'], 409);
        }
    } */

    public function userManagementList(Request $request){
        $searchTerm = $request->search_keyword;
        $managementList = User::where('user_role_id', Constants::USER_ARTIST)
            ->leftJoin('user_profiles', 'users.user_id', '=', 'user_profiles.user_profile_id')
            ->orderBy('user_status', 'ASC')
            ->where(function ($q) use ($searchTerm, $request) {
                if ($searchTerm) {
                    $q->where('user_profile_full_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('user_email', 'like', '%' . $searchTerm . '%');
                }
            })
            ->orderBy($request->sort, $request->sort_dirc)
            ->paginate($request->limit);

        if($managementList->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $managementList,
                    "message" => "Here are the list of user management",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available user management",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function viewUserProfile(Request $request, $id){

        $viewUserPortfolio = User::where('users.user_id', $id)
            ->join('user_profiles', 'users.user_id', 'user_profiles.user_id')
            ->first();

        if($viewUserPortfolio){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $viewUserPortfolio,
                    "message" => "Here are the details of user portfolio details.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "User portfolio not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function getUserSpecificMintingList(Request $request, $id){

        $getMintingList = User::where('users.user_id', $id)
            ->join('tokens', 'users.user_id', 'tokens.user_id')
            ->join('transactions', 'tokens.token_id', 'transactions.transaction_token_id')
            ->where('transactions.transaction_type', Constants::TRANSACTION_MINTING)
            ->where('transactions.transaction_status', Constants::TRANSACTION_PENDING)
            ->where('tokens.token_status', Constants::PENDING)
            ->paginate($request->limit);

        if($getMintingList->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $getMintingList,
                    "message" => "Here are the details of artist/collector minting list.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Artist/collector not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function getReadyTokens(Request $request, $id){

        $getReadyPortfolio = Token::where('token_status', Constants::READY)
            ->where('user_id', $id)
            ->paginate($request->limit);

        if($getReadyPortfolio->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $getReadyPortfolio,
                    "message" => "Here are the porfolio list of specific users.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Portfolio list not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function viewSpecificPortfolio(Request $request, $id){
        $viewSpecificPortfolio = Token::where('tokens.token_id', $id)
        ->join('transactions', 'tokens.token_id', 'transactions.transaction_token_id')
        ->first();

        if($viewSpecificPortfolio){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $viewSpecificPortfolio,
                    "message" => "Here are the details of specific portfolio details.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Specific portfolio snot found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function getSpecificRequestMintingDetailsArtist(Request $request, $id){
        $viewSpecificRequestMinting = Token::where('tokens.token_id', $id)
        ->join('transactions', 'tokens.user_id', 'transactions.transaction_token_id')
        ->first();

        if($viewSpecificRequestMinting){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $viewSpecificRequestMinting,
                    "message" => "Here are the details of specific request minting details.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Specific request minting snot found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function deactivateUser(Request $request){
        $deactivateUser = User::find($request->user_id);

        if($deactivateUser){
            $deactivateUser->update($request->all());
                $response=(object)[
                        "success" => true,
                        "result" => [
                        "message" => "This Artist/Collector has been deactivated successfully",
                    ]
                ];
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        } 
        return response()->json($response, 200);
    }

    public function activateUser(Request $request){
        $activateUser = User::find($request->user_id);

        if($activateUser){
            $activateUser->update($request->all());
                $response=(object)[
                        "success" => true,
                        "result" => [
                        "message" => "This Artist/Collector has been activated successfully",
                    ]
                ];
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        } 
        return response()->json($response, 200);
    }
}