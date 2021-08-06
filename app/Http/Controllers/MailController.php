<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
class MailController extends Controller
{
public function mail() {
    $data = array('name'=>"Ronald");
    Mail::send('mail/mail', $data, function($message) {
        $message->to('ronaldcomendador20@gmail.com', 'Ronald')->subject('Test Mail from Ronald');
        $message->from('ronaldcomendador20@gmail.com','Ronald');
    });
    echo "Email Sent. Check your inbox.";
}
}