<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\User_profile;
use App\Token;
use App\Transaction;
use App\Notifications;
use DB;
use App\Constants;
use Illuminate\Support\Facades\Mail;

class TokenController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    protected CONST MERCHANT_ID = 'LEENTECH';
    protected CONST MERCHANT_PASS = 'Da5qgHfEw3zN';
    protected CONST MERCHANT_API_KEY = 'bec973b72e20e653ddc54c0b37cbf18a254b6928';
    protected CONST MODE = 'development';
    
    public function __construct(){
        $this->middleware('auth');
    }

    public function getTokens(Request $request){
        $tokens = new Token();
        $searchTerm = $request->search_key;
        if($searchTerm){
            $tokens = $tokens->where('token_title', 'like', '%' . $searchTerm. '%')->orWhere('token_description', 'like', '%' . $searchTerm. '%');
        }

        $tokens = $tokens->with(['transactions' => function ($q) {
            $q->orderBy('transaction_id', 'DESC');
        }])->orderBy('token_id','DESC')->paginate($request->limit);
        
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
            $token_details['token_properties'] = json_decode(json_decode($token_details->token_properties));

          
            if(!$token_details->owner->profile->user_profile_avatar){
                $token_details->owner->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
            if(!$token_details->creator->profile->user_profile_avatar){
                $token_details->creator->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }

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
        /* $wallet_details = DB::select("SELECT * from wallets WHERE user_id='".$request->input('user_id')."' and wallet_status=1");
        if($wallet_details){ */
            // try {
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
                $token->token_status = 1;
                $token->token_owner = Auth::user()->user_id;
                $token->token_creator = Auth::user()->user_id;
                $token->token_on_market = $request->input('put_on_market');

                $token->save();
                $token_id = $token->token_id;
                $transaction = Transaction::create(
                    [
                        "user_id" =>  Auth::user()->user_id,
                        "transaction_token_id" => $token_id,
                        "transaction_type" => 1,
                        "transaction_payment_method" =>  1,
                        "transaction_details" =>  1,
                        "transaction_service_fee" =>  1,
                        "transaction_urgency"   => 1,
                        "transaction_gas_fee" =>  1,
                        "transaction_allowance_fee" =>  1,
                        "transaction_grand_total" => 1,
                        /* "transaction_payment_method" =>  $request->input('transaction_payment_method'),
                        "transaction_details" =>  $request->input('transaction_details'),
                        "transaction_service_fee" =>  $request->input('transaction_service_fee'),
                        "transaction_urgency"   => $request->input('token_urgency'),
                        "transaction_gas_fee" =>  $request->input('transaction_gas_fee'),
                        "transaction_allowance_fee" =>  $request->input('transaction_allowance_fee'),
                        "transaction_grand_total" =>  $request->input('transaction_grand_total'), */
                        "transaction_status" =>  1,
                    ]
                );

                if($token){

                    $user_details = User::find(Auth::user()->user_id);
                    
                    Notifications::create([
                        'notification_message' => '<p><b>'.$user_details->profile->user_profile_full_name.'</b> request for minting.</p>',
                        'notification_to' => 0,
                        'notification_from' => Auth::user()->user_id,
                        'notification_item' => $token_id,
                        'notification_type' => Constants::NOTIF_MINTING_REQ,
                    ]);
                }

                $response=(object)[
                    "success" => true,
                    "result" => [
                        "token" => $token_id,
                        "message" => "Your artwork has been successfully request for minting."
                    ]
                ];
                return response()->json($response, 200);
            /* }catch (\Exception $e) {
                return response()->json(['message' => 'Request for Minting Failed!'], 409);
            } */
        /* }else{
            return response()->json(['message' => 'Please set up your wallet first'], 409);
        } */
    }

    public function portfolio(Request $request){
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
    }

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
                    $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Miting Status Update');
                    $message->from('support@tokreate.com','Tokreate');
                });

                Notifications::create([
                    'notification_message' => $msg,
                    'notification_to' => $user_details->user_id,
                    'notification_from' => Auth::user()->user_id,
                    'notification_type' => Constants::NOTIF_MINTING_RES,
                ]);

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
        $tokens = $tokens->join('transactions','transactions.transaction_token_id','tokens.token_id');
        if($searchTerm){
            $tokens->where('token_title', 'like', '%' . $searchTerm. '%')
            ->orWhere('token_description', 'like', '%' . $searchTerm. '%');
        }
        if($request->filter_urgency !== ""){
            $tokens = $tokens->where('transaction_urgency', $request->filter_urgency);
        }
        $token_list = $tokens->with(['owner'])->orderBy('token_status', 'ASC')->paginate($request->limit);

        foreach ($token_list as $key => $token) {
            if(!$token->owner->profile->user_profile_avatar){
                $token->owner->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
            if(!$token->creator->profile->user_profile_avatar){
                $token->creator->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
        }
        

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

    public function collection(Request $request){
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
    }

    private function getHost() {
        if(SELF::MODE == 'development') {
            return 'test.dragonpay.ph';
        } else {
            return 'gw.dragonpay.ph';
        }
    }

    private function getBaseUrl() {
        if(SELF::MODE == 'development') {
            return 'https://test.dragonpay.ph/';
        } else {
            return 'https://gw.dragonpay.ph/';
        }
    }
    public function payment(Request $request){
        $transaction = Transaction::findorfail($request->input('transaction_token_id'));

        if($transaction){
            $transaction->update($request->all());
        }
        $params = array(
            'merchantid' => SELF::MERCHANT_ID,
            'txnid' => $request->input('transaction_token_id'),
            'amount' => $request->transaction_grand_total,
            'ccy' => 'PHP',
            'description' => 'test',
            'email' => 'kaelreyes12@hotmail.com',
        );

        $params['amount'] = number_format($params['amount'], 2, '.', '');
        $params['key'] = SELF::MERCHANT_PASS;
        $digest_string = implode(':', $params);
        unset($params['key']);
        $params['digest'] = sha1($digest_string);
        if($request->proc_id) {
            $params['procid'] = $request->proc_id;
        }

        $url = $this->getBaseUrl() . 'Pay.aspx?' . http_build_query($params, '', '&');

       
        $response=(object)[
            "result" => [
                "url" =>  $url,
                "token_id" => $request->input('transaction_token_id'),
                "payment_method" => $request->input('transaction_payment_method')
            ]
        ];
        return response()->json($response, 200);
        // return $url
    }

    public function webhook(Request $request) {
        if($request->status == 'S') {
            try {
                Transaction::where('token_transaction_id', $request->txnid)->update([
                    'transaction_status' => Constants::TRANSACTION_SUCCESS
                ]);
                $result = Transaction::where('token_transaction_id', $request->txnid)->first();
                return responseWithMessage(200, "Success", $result);
            } catch(\Throwable $th) {
                return $th;
            }
        }else{
            try {
                Transaction::where('token_transaction_id', $request->txnid)->update([
                    'transaction_status' => Constants::TRANSACTION_FAILED
                ]);
                $result = Transaction::where('token_transaction_id', $request->txnid)->first();
                return responseWithMessage(200, "Success", $result);
            } catch(\Throwable $th) {
                return $th;
            }
        }
    }
}