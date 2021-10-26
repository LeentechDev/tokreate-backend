<?php

namespace App\Http\Controllers;

use App\Constants;
use App\CronJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Terms_and_conditions;
use App\User_profile;
use DB;
use App\User;

class TermsandConditionController  extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */


    public function viewTermsandConditions()
    {
        $terms = DB::select("SELECT * FROM `terms_and_conditions`
        LEFT JOIN `user_profiles` ON `user_profiles`.`user_id` = `terms_and_conditions`.`terms_updated_by`
        WHERE id='1'");

        if ($terms) {
            $response = (object)[
                "success" => true,
                "result" => [
                    "datas" => $terms,
                    "message" => "Here are the details of terms and condition.",
                ]
            ];
            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "terms and condition not found.",
                ]
            ];
            return response()->json($response, 200);
        }
    }


    public function updateTermsandConditions(Request $request)
    {

        $terms = Terms_and_conditions::find($request->id);

        if ($terms) {
            $terms->update($request->all());
            $response = (object)[
                "success" => true,
                "result" => [
                    "message" => "Terms and condition has been successfully updated",
                ]
            ];

            $users = User::where('user_role_id', '!=', Constants::USER_ADMIN)->pluck('user_id')->toArray();

            $batches = array_chunk($users, 10);
            $no_batch = count($batches);

            for ($i = 0; $i < $no_batch; $i++) {

                $batch = implode(',', $batches[$i]);

                CronJobs::create([
                    'user_id' => $batch,
                    'from_user_id' => Auth::user()->user_id,
                    'content' => "",
                    'type'    => Constants::CRON_TERMS,
                    'status'  => Constants::CRON_STATUS_PENDING
                ]);
            }

            return response()->json($response, 200);
        } else {
            $response = (object)[
                "success" => false,
                "result" => [
                    "message" => "Invalid parameters",
                ]
            ];
        }
        return response()->json($response, 200);
    }
}
