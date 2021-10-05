<?php

namespace App\Lib;

use App\Model\sys_param;
use App\Model\User;
use UUID;
use DB;

/**
 * Class TokenLib
 * Token函式庫
 */

class TokenLib {

    /**
     * Token 是否已經存在
     * @param int $b_cust_id
     * @param string $token
     * @param string $now
     * @return string
     */
    public static function isTokenExist($b_cust_id = 0, $token='', $type= 'app', $now = '')
    {
        if(!$now) $now = date('Y-m-d H:i:s');
        $getTokenInfo = '';

        if($b_cust_id)
        {
            $getTokenInfo = DB::table('b_token')
                ->select('token','apiKey','b_cust_id')
                ->where('b_cust_id',$b_cust_id)
                ->where('type',$type)
                ->where('edate','>=',$now)
                ->where('sdate','<=',$now)
                ->where('isClose','N')->first();
        }
        elseif($token)
        {
            $getTokenInfo = DB::table('b_token')
                ->select('token','apiKey','b_cust_id')
                ->where('token',$token)
                ->where('type',$type)
                ->where('edate','>=',$now)
                ->where('sdate','<=',$now)
                ->where('isClose','N')->first();
        }

        return $getTokenInfo;
    }

    public static function isApiKey($type = 'app', $apiKey)
    {
        $now = date('Y-m-d H:i:s');
        return ($apiKey && $type)? DB::table('b_token')
                        ->where('apiKey',$apiKey)
                        ->where('type',$type)
                        ->where('edate','>=',$now)
                        ->where('sdate','<=',$now)
                        ->where('isClose','N')->count() : 0;
    }

    /**
     * 取得 Token (如果沒有，則新建)
     * @param int $b_cust_id
     * @param string $token
     * @param string $isNewToken
     * @return array
     */
    public static function getToken($type = 'app',$b_cust_id = 0, $token = '')
    {
        //參數
        $id            = 0;
        $now           = date('Y-m-d H:i:s');
        $isGenNewToken = 1; //產生新token
        $tokenLimit    = sys_param::getParam('TOKEN_MAX_LIVE_DAYS',7);//預設 30 天存活時間


        //1. 先檢查token 是否存在
        if($b_cust_id || $token)
        {
            $hasToken = TokenLib::isTokenExist($b_cust_id, $token, $type);
            if(isset($hasToken->token) && strlen($hasToken->token))
            {
                $token         = $hasToken->token;
                $apiKey        = $hasToken->apiKey;
                $b_cust_id     = $hasToken->b_cust_id;

                $isGenNewToken = 0;
                $id            = $token;
            }
        }
        //2. 創造一個新的token
        if($isGenNewToken && $b_cust_id)
        {
            $token  = UUID::generate(3,$b_cust_id.$now,Uuid::NS_DNS)->string;
            $apiKey = str_replace('-','',UUID::generate(5,$b_cust_id.$now,Uuid::NS_DNS)->string);
            $sdate  = $now;
            $edate  = date('Y-m-d H:i:s',strtotime($now . '+'.$tokenLimit.' day'));

            $INS = array();
            $INS['type']        = $type;
            $INS['token']       = $token;
            $INS['apiKey']      = $apiKey;
            $INS['sdate']       = $sdate;
            $INS['edate']       = $edate;
            $INS['b_cust_id']   = $b_cust_id;
            $INS['created_at']  = $now;
            $INS['updated_at']  = $now;

            //新增
            $id = DB::table('b_token')->insertGetId($INS);
        }
        //3. 判斷是否有token
        $isToken   = ($id)? 'Y' : 'N' ;
        $tokenTag  = ($id)? $token : '' ;
        $apiKeyTag = ($id)? $apiKey : '' ;

        return ['ret'=>$isToken,'apiKey'=> $apiKeyTag, 'token'=>$tokenTag];
    }

    /**
     * Token ＆ apiKey 失效
     */
    public static function closeToken($bid,$type = '')
    {
        if(!$bid) return false;
        if(!User::isExist($bid)) return false;

        $now = date('Y-m-d H:i:s');
        $data = array();
        $data['isClose']     = 'Y';
        $data['close_user']  = $bid;
        $data['close_stamp'] = $now;
        //1. 將token 失效
        $b_token = DB::table('b_token')->where('b_cust_id',$bid)->where('isClose','N');
        if($type)
        {
            $b_token = $b_token->where('type',$type);
        }
        $ret = $b_token->update($data);
        //2. 將push_id 失效
        if($ret)
        {
            //DB::table('b_cust')->where('id',$bid)->update(['pusher_id'=>'']);
        }

        return $ret;
    }

}
