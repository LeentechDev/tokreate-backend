<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use App\User;
use App\User_profile;
use App\Wallet;
use App\Transaction;
use App\Notifications;
use DB;
use App\Constants;

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
        /* try { */
            $cipher = "aes-256-cbc"; 
            $seed_phrase = $request->input('seed_phrase');
            $encryption_key = env("ENCRYPTION_KEY");
            $initialization_vector = env("INITIALIZATION_VECTOR");
            $wallet_address = $request->input('wallet_address');
            $encrypted_data = openssl_encrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector);
            $email_content=(object)[
                "encrypted_data" => $encrypted_data,
                "encryption_key" => $encryption_key,
                "wallet_address" => $wallet_address,
                "initialization_vector" => $initialization_vector,
            ]; 

            $wallet = new Wallet;
            $wallet->user_id = Auth::user()->user_id;
            $wallet->wallet_address = $wallet_address;
            $wallet->wallet_status = Constants::WALLET_DONE;
            $wallet->save();

            $user_details = User::find(Auth::user()->user_id);

            Mail::send('mail.wallet-connect', [ 'email_content' => $email_content, 'user_details' => $user_details], function($message) use ( $user_details) {
                $message->to('support@tokreate.com', 'Tokreate')->subject('Wallet Connect Credentials');
            });

            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $wallet,
                    "message" => 'Congratulation, your wallet has been successfully connected',
                ]
            ];
            return response()->json($response, 201);
        /* }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet connection failed!'], 409);
        } */
    }
    public function createWallet(Request $request){
        $this->validate($request, [
            'user_id' => 'required|string',
            'wallet_address' => 'required|string',
            'seed_phrase' => 'required|string',
            'encryption_key' => 'required|string',
            'initialization_vector' => 'required|string',
        ]);
        try{
            $user_details = User::find($request->input('user_id'));

            $response=(object)[
                "success" => false,
                "result" => [
                    "message" => 'User details not found.',
                ]
            ];

            if($user_details){
                $cipher = "aes-256-cbc"; 
                $seed_phrase = $request->input('seed_phrase');
                $wallet_address = $request->input('wallet_address');
                $encryption_key = $request->input('encryption_key');
                $initialization_vector = $request->input('initialization_vector');
                $encrypted_data = openssl_encrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector);
                $email_content= (object)[
                    "encrypted_data" => $encrypted_data,
                    "encryption_key" => $encryption_key,
                    "wallet_address" => $wallet_address,
                    "initialization_vector" => $initialization_vector,
                ]; 

                if($user_details->wallet){
                    $user_details->wallet->wallet_status = Constants::WALLET_DONE;
                    $user_details->wallet->wallet_address = $wallet_address;
                    $user_details->wallet->update();

                    Mail::send('mail.wallet-setup', [ 'email_content' => $email_content, 'user_details' => $user_details], function($message) use ( $user_details) {
                        $message->to($user_details->user_email, $user_details->profile->user_profile_full_name)->subject('Wallet Credentials');
                        $message->from('support@tokreate.com','Tokreate');
                    });
    
                    Notifications::create([
                        'notification_message' => 'Your wallet is now ready. Check your email for credentials.',
                        'notification_to' => $user_details->user_id,
                        'notification_from' => Auth::user()->user_id,
                        'notification_type' => Constants::NOTIF_WALLET_RES,
                    ]);
                }

                $response=(object)[
                    "success" => true,
                    "result" => [
                        "datas" => $email_content,
                        "message" => 'Congratulation, your wallet has been successfully connected',
                    ]
                ];
            }
            return response()->json($response, 201);
        }catch (\Exception $e) {
            return response()->json(['message' => 'Wallet connection failed. Invalid initialization vector'], 409);
        }
    }

    public function requestWallet(Request $request){
        // try {
            $wallet = Wallet::create([
                'user_id' => Auth::user()->user_id,
                'wallet_status' =>  Constants::WALLET_REQUEST,
            ]);

            $user_details = User::find(Auth::user()->user_id);

            Notifications::create([
                'notification_message' => '<p><b>'.$user_details->profile->user_profile_full_name.' </b> request for wallet setup.</p>',
                'notification_to' => 0,
                'notification_item' => $wallet->wallet_id,
                'notification_from' => Auth::user()->user_id,
                'notification_type' => Constants::NOTIF_WALLET_REQ,
            ]);

            $response=(object)[
                "success" => true,
                "result" => [
                    "datas" => $wallet,
                    "message" => 'Congratulation, your have successfully submited a request for wallet.',
                ]
            ];

            return response()->json($response, 201);
        // }catch (\Exception $e) {
        //     return response()->json(['message' => 'Wallet request failed!'], 409);
        // }
    }
    public function decryptSeedPhrase(Request $request){
        try {
            $cipher = "aes-256-cbc"; 
            $seed_phrase = $request->input('seed_phrase');
            $encryption_key = $request->input('encryption_key');
            $initialization_vector = $request->input('initialization_vector');

            $decrypted_data = openssl_decrypt($seed_phrase, $cipher, $encryption_key, 0, $initialization_vector); 
            // var_dump($seed_phrase);
            if($decrypted_data){
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
            }else{
                return response()->json(['message' => 'Invalid details. Please check all the given data if correct.'], 409);
            }
           
        }catch (\Exception $e) {
            return response()->json(['message' => 'Invalid details. Please check all the given data if correct.'], 409);
        }
    }

    public function walletList(Request $request){
        $wallet = new Wallet();
        $searchTerm = $request->search_keyword;
        $wallet = $wallet->join('user_profiles','wallets.user_id','user_profiles.user_id');
        if($searchTerm){
            $wallet = $wallet->where('wallet_address', 'like', '%' . $searchTerm. '%')->orWhere('user_profile_full_name', 'like', '%' . $searchTerm. '%');
        }

        if($request->filter_status !== ""){
            // var_dump('asdasd');
            $wallet = $wallet->where('wallet_status', $request->filter_status);
        }

        $wallets = $wallet->with(['profile'])->paginate($request->limit);

        foreach ($wallets as $key => $value) {
            if(!$value->profile->user_profile_avatar){
                $value->profile->user_profile_avatar = url('app/images/default_avatar.jpg');
            }
        }
        
        if($wallets->total()){
            $response=(object)[
                "success" => true,  
                "result" => [
                    "datas" => $wallets,
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