<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\Constants;
use App\Notifications;
use DB;

class NotificationController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth');
    }

    public function read(Request $req){
        var_dump($req->post('notif_id'));
        $notif = Notifications::find($req->notif_id);
        $notif->notification_read_by = 1;
        $notif->update();

        $response=(object)[
            "success" => true,
        ];
        return response()->json($response, 200);
    }

    public function list(Request $request){
        $page = $request->page;
        $notifications = Notifications::paginate(10);

        if($notifications){
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Token status has been successfully updated."
                ]
            ];
            return response()->json($response, 200);
        }else{
            return response()->json(['message' => 'No notifications.'], 409);
        }
       
    }

}


    
