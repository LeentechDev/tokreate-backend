<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Notifications;
use App\Constants;
use Illuminate\Support\Facades\Log;

class CronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:notification';

    protected $description = 'Sending out emails notification to users';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $crons = CronJobs::where('status', Constants::CRON_STATUS_PENDING)->limit(3)->get();

            foreach ($crons as $cron) {

                switch ($cron->type) {

                    case Constants::CRON_TERMS:
                        $users = explode(',', $cron->user_id);
                        foreach ($users as $user_id) {
                            $user = User::where('user_id', $user_id)->first();

                            $email_msg = "<p>Dear <b>" . $user->profile->user_profile_full_name . "</b>, <br> We are continuing to develop more features on our websites. To address these changes, we've updated our <a href='" . ENV('APP_URL') . "/termsandcondition'>Terms of Use</a>.</p>";
                            $subject = "Terms and Condition Updates";

                            Mail::send('mail.email', ['msg' => $email_msg], function ($message) use ($user, $subject) {
                                $message->to($user->user_email, $user->profile->user_profile_full_name)->subject($subject);
                                $message->from('support@tokreate.com', 'Tokreate');
                            });

                            Notifications::create([
                                'notification_message' => $email_msg,
                                'notification_to' => $user_id,
                                'notification_from' => 0,
                                'notification_type' => Constants::NOTIF_MINTING_RES,
                            ]);
                        }
                        break;

                    case Constants::CRON_POLICY:
                        $users = explode(',', $cron->user_id);
                        foreach ($users as $user_id) {
                            $user = User::where('user_id', $user_id)->first();

                            $email_msg = "<p>Dear <b>" . $user->profile->user_profile_full_name . "</b>, <br> We are continuing to develop more features on our websites. To address these changes, we've updated our <a href='" . ENV('APP_URL') . "/privacypolicy'>Privacy Policy</a>.</p>";
                            $subject = "Privacy Policy";

                            Mail::send('mail.email', ['msg' => $email_msg], function ($message) use ($user, $subject) {
                                $message->to($user->user_email, $user->profile->user_profile_full_name)->subject($subject);
                                $message->from('support@tokreate.com', 'Tokreate');
                            });

                            Notifications::create([
                                'notification_message' => $email_msg,
                                'notification_to' => $user_id,
                                'notification_from' => 0,
                                'notification_type' => Constants::NOTIF_MINTING_RES,
                            ]);
                        }
                        break;

                    case Constants::CRON_COMMISSION:
                        $users = explode(',', $cron->user_id);
                        foreach ($users as $user_id) {
                            $user = User::where('user_id', $user_id)->first();

                            $email_msg = '<p>Hi <b>' . $user->profile->user_profile_full_name . '</b>,</p>' . $cron->content;
                            $subject = "Update on Commission Rate";

                            Mail::send('mail.transfer-status', ['msg' => $email_msg], function ($message) use ($user, $subject) {
                                $message->to($user->user_email, $user->profile->user_profile_full_name)->subject($subject);
                                $message->from('support@tokreate.com', 'Tokreate');
                            });

                            Notifications::create([
                                'notification_message' => $email_msg,
                                'notification_to' => $user_id,
                                'notification_from' => 0,
                                'notification_type' => Constants::NOTIF_MINTING_RES,
                            ]);
                        }
                        break;

                    default:
                        # code...
                        break;
                }
                $cron->status = Constants::CRON_STATUS_DONE;
                $cron->save();
            }
            Log::info('Success');
        } catch (\Throwable $th) {
            Log::info($th);
        }

        $this->info('Word of the Day sent to All Users');
    }
}
