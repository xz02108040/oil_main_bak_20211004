<?php

namespace App\Lib;

use App\Mail\TestMailOrder;
use Mail;
use UUID;
use DB;

/**
 * Mail 推播
 * @package App\Lib
 */

class MailLib {

    public static function toMail($uid,$email,$type,$title,$body,$push_mode = 1)
    {
        if(!$email) return '';
        $ret = 1;
        $order = ['title'=>$title,'content'=>$body];
        $result = Mail::to($email)->send(new TestMailOrder((object)$order));

        $resultAry = ['results'=>$result,'failure'=>0,'success'=>0];
        LogLib::putPushLog($uid,$email,$type,$body,($resultAry),$ret);

        return $result;
    }

}
