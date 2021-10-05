<?php

namespace App\Lib;

use App\Model\bc_type_app;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_rfid;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_used_rfid;
use App\Model\View\view_user;
use Auth;
use DB;

/**
 * Class CheckLib
 * @package App\Lib
 * 檢查函式庫
 */

class CheckLib {
    /**
     * 檢查 行動電話是否重複
     * @param $bcid
     * @param int $extID
     * @param int $sid
     * @return int
     */
    public static function checkMobileExist($mobile, $extID = 0, $sid = 0)
    {
        $ret = 0;
        if(!$mobile) return $ret;
        /* 1. 檢查承攬商成員 重複依據（身分證）
         *      1 = view_user               檢查  只要是會員之中 有無出現重複身分證
         *      2 = view_supply_member      檢查  目前進行中的承攬項目中參與會員 有無出現重複身分證
         *      3 = view_user               檢查  針對 特定承攬商檢核 有無出現重複身分證
        */
        $rule = 1;//sys_param::getParam('BCID_RULE',1);

        switch ($rule)
        {
            case 1:
            default:
                $ret = view_user::isMobileExist($mobile,$extID);
                break;
        }

        return $ret;
    }
    /**
     * 檢查 身分證是否重複
     * @param $bcid
     * @param int $extID
     * @param int $sid
     * @return int
     */
    public static function checkBCIDExist($bcid, $extID = 0, $sid = 0)
    {
        $ret = 0;
        if(!$bcid) return $ret;
        /* 1. 檢查承攬商成員 重複依據（身分證）
         *      1 = view_user               檢查  只要是會員之中 有無出現重複身分證
         *      2 = view_supply_member      檢查  目前進行中的工程案件中參與會員 有無出現重複身分證
         *      3 = view_user               檢查  針對 特定承攬商檢核 有無出現重複身分證
        */
        $rule = sys_param::getParam('BCID_RULE',1);

        switch ($rule)
        {
            //檢查特定承攬商中會員 有無重複身分證 <承攬商>
            case 3:
                $ret = view_user::isBCIDExist($bcid,$extID,0,$sid);
                break;
            //檢查目前工程案件之承攬商的會員 有無重複身分證 <成員，工程案件，掛名>
            case 2:
                $ret = view_door_supply_member::isBCIDExist($bcid,$extID);
                break;
            //檢查會員是否有重複身分證<成員，職員皆不可重複>
            case 1:
            default:
                $ret = view_user::isBCIDExist($bcid,$extID);
                break;
        }

        return $ret;
    }

    /**
     * 判斷是否為會員 （帳號/密碼）
     * @param $account
     * @param $password
     * @param int $isLog
     * @param int $isSource
     * @param array $source
     * @return bool|int
     */
    public static function isAppAccount($account, $password, $isLog = 0, $isSource = 9, $source = array('ip'=>'','imei'=>''))
    {
        if(!$account && !$password) return false;
        $b_cust_id = 0;
        $imei = (isset($source['imei']))? $source['imei'] : '';
        $ip   = (isset($source['ip']))? $source['ip'] : '';

        //判斷 帳號/密碼
        if (Auth::attempt(['account' => $account, 'password' => $password, 'isClose'=> 'N', 'isLogin'=>'Y']))
        {
            $b_cust_id = Auth::id();
        }
        //dd([$account,$password,$b_cust_id]);

        //判斷ＡＰＰ身份
//        if($b_cust_id && Auth::user()->bc_type_app == 0)
//        {
//            //沒有ＡＰＰ身份-阻擋
//            $b_cust_id = 0;
//        }

        //Log 登入成功/失敗記錄
        $isSuc = ($b_cust_id)? 'Y' : 'N';
        if($isLog) LogLib::putLoginLog($isSuc, $account, $ip, $imei, $isSource);
        return $b_cust_id;
    }

    /**
     * 是否超過嘗試登入上限
     * @param $account
     * @param $imei
     * @param $ip
     * @return bool|int
     */
    public static function isOverLoginErrLimit($account,$imei,$ip)
    {
        //嘗試登入錯誤次數上限
        $limit     = sys_param::getParam('API_ERR_LOGIN_MAX_TIMES',3);
        //如果沒有，則不檢查
        if(!$limit) return -1;

        //檢核時間區間
        $maxtime   = sys_param::getParam('API_ERR_LOGIN_TIME_RANGER',300);
        $imei      = (strlen($imei))? $imei : 'NOIMEI';
        //取得 目前嘗試登入錯誤次數
        $getOverLoginErrTimes = DB::table('log_login')
            ->Where('created_at','>=',date("Y-m-d H:i:s",time()-$maxtime))
            ->Where('is_suc','N')
            ->Where(function ($where ) use ($account,$imei,$ip) {
                $where->Where('account', $account)
                    ->orWhere('imei',$imei)
                    ->orWhere('ip',$ip);
            })
            ->count();
        //dd([$getOverLoginErrTimes,$limit]);
        return ($getOverLoginErrTimes >= $limit)? $getOverLoginErrTimes : 0;
    }

    /**
     * 檢核 門禁伺服器請求<設備識別碼,場地RFID> 是否合格
     * @param string $email
     * @return number
     */
    public static function isStoreDeviceToken($account,$pwd){
        $factory_id = $door_id = $door_type = 0;
        $door_name  = '';
        if(!$account || !$pwd) return [$factory_id,$door_id,$door_name,$door_type];
        //1. 檢查
        list($factory_id,$door_id,$door_name,$door_type) = b_factory_d::checkDoorInfo($account,$pwd);
        return [$factory_id,$door_id,$door_name,$door_type];
    }

    /**
     * 檢核是否出現數字&特殊符號
     * @param string $email
     * @return number
     */
    public static function isNameFormat($account){
        $ret = 1;
        if(preg_match('/[0-9]/', $account)){
            $ret = 0;
        } elseif(preg_match('/[@#$%^&+=]/', $account)){
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 檢核密碼 英數字同時存在,最少要6碼
     * @param string $email
     * @return number
     */
    public static function isPasswordFormat($pwd){
        $ret = 0;
        if(preg_match('/^(?!.*[^\x21-\x7e])(?=.{6})(?=.*[0-9])(?=.*[a-zA-Z]).*$/', $pwd)){
            $ret = 1;
        }

        return $ret;
    }

    /**
     * 檢核帳號 是否出現非英文數字
     * @param string $value
     * @return number
     */
    public static function isAccountFormat($value){
        $ret = 0;
        if(preg_match("/^[a-z0-9]*$/",$value)){
            $ret = 1;
        }

        return $ret;
    }

    //判斷是否為Ｅmail
    public static function isMail($email){
        if(strlen($email) < 6) return false;
        return preg_match('/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/', $email);
    }

    //判斷是否為時間格式
    public static function isTime($str){
        if(strlen($str) == 4) $str = '0'.$str;
        return preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $str);
    }

    //判斷是否為日期格式
    public static function isDate($str){

        if (!preg_match("/^([1,2]{1}[0-9]{3})-([0-9]{1,2})-([0-9]{1,2})$/", $str)) {
            return false;
        }

        list($data['ty'], $data['tm'], $data['td']) = explode('-', $str);
        if (!checkdate($data['tm'], $data['td'], $data['ty'])) {
            return false;
        }

        return true;
    }

    /**
     * 身分證字號檢查
     * @param string $id_old
     * @param string $country
     * @return number
     */
    public static function isBcID($id,$country="TW") {
        $ret = 0;
        //統一大寫
        $country = strtoupper($country);
        //選擇國家
        switch ($country){
            //臺灣
            case 'TW':
            default:

                if (strlen($id) != 10)
                    return 0;

                for ($i = 0; $i < 10; $i++) {
                    $id1[$i] = $id[$i];
                }

                if (($id1[0] < 'A') || (($id1[0] > 'Z') && ($id1[0] < 'a')) || ($id1[0] > 'z'))
                    return 0;

                for ($i = 1; $i < 10; $i++) {
                    if (!($id1[$i] <= '9' && $id1[$i] >= '0')) {
                        return 0;
                    }
                }

                if (('a' <= $id1[0]) && ($id1[0] <= 'z'))
                    $id1[0] = ord($id1[0]) - 32;

                else if (('A' <= $id1[0]) && ($id1[0] <= 'H'))
                    $id1[0] = ord($id1[0]) - 55;

                else if (('J' <= $id1[0]) && ($id1[0] <= 'N'))
                    $id1[0] = ord($id1[0]) - 56;

                else if (('P' <= $id1[0]) && ($id1[0] <= 'V'))
                    $id1[0] = ord($id1[0]) - 57;

                else if ($id1[0] == 'X')
                    $id1[0] = 30;

                else if ($id1[0] == 'Y')
                    $id1[0] = 31;

                else if ($id1[0] == 'W')
                    $id1[0] = 32;

                else if ($id1[0] == 'Z')
                    $id1[0] = 33;

                else if ($id1[0] == 'I')
                    $id1[0] = 34;

                else if ($id1[0] == 'O')
                    $id1[0] = 35;

                for ($k = 1; $k < 10; $k++)
                    $id1[$k] = ord($id1[$k]) - 48;

                if (($id1[1] != 1) && ($id1[1] != 2))
                    return 0;

                $a = (($id1[0] / 10) + ($id1[0] % 10) * 9 + $id1[1] * 8 + $id1[2] * 7 + $id1[3] * 6 + $id1[4] * 5 + $id1[5]
                    * 4 + $id1[6] * 3 + $id1[7] * 2 + $id1[8] + $id1[9]);

                if ($a % 10 == 0)
                    return 1;
                else
                    return 0;
                break;
        }
    }

    //判斷中國手機格式
    public static function isMobile($mobile, $country = 1)
    {
        $mobile = str_replace('-','',$mobile);
        //TW
        if($country == 2)
        {
            return (preg_match('/^09[0-9]{8}$/',$mobile))? true : false;
        } else {
            return (preg_match('/^(?:13|15|18)[0-9]{9}$/',$mobile))? true : false;
        }

    }

    //判斷是否為ＪＳＯＮ
    public static function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

}
