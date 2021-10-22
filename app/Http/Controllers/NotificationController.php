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
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function read(Request $req)
    {
        /* var_dump($req->post('notif_id')); */
        $notif = Notifications::find($req->notif_id);
        $notif->notification_read_by = 1;
        $notif->update();

        $response = (object)[
            "success" => true,
        ];
        return response()->json($response, 200);
    }

    public function list(Request $request)
    {
        $page = $request->page;

        try {
            /* if(Auth::user()->user_role_id === Constants::USER_ADMIN){
                $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
                ->where('notification_to', 0)->paginate(10);
            }else{ */
            $notifications = Notifications::join('user_profiles', 'user_profiles.user_id', 'notification.notification_from')
                ->where('notification_to', Auth::user()->user_id)->paginate(10);
            /* } */


            if ($notifications) {
                $response = (object)[
                    "success" => true,
                    "result" => [
                        "datas" => $notifications,
                        "message" => "List of notifications"
                    ]
                ];
                return response()->json($response, 200);
            } else {
                return response()->json(['message' => 'No notifications.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => "Something wen't wrong"], 409);
        }
    }
}
