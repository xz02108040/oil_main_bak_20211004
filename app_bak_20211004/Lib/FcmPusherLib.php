<?php

namespace App\Lib;

use App\Model\User;
use UUID;
use DB;

/**
 * FCM 推播
 * @package App\Lib
 */

class FcmPusherLib {

    public static function pushSingleDevice($uid,$token,$type,$title,$body)
    {
        if(!$token) return '';
        //2021-03-11
        $result = LogLib::putPushQueueLog($uid,$token,$type,$title,$body);

        return $result;
    }

}
