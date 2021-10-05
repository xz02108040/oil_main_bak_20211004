<?php

namespace App\Lib;

use App\Model\Bcust\b_cust_a;
use App\Model\dsa_store_send_rpt;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\log_cron;
use UUID;
use DB;
use Storage;
use Lang;

/**
 * Class LogLib
 * @package App\Lib
 * Log函式庫
 */

class LogLib {
    /**
     * 取得 場地音訊盒佇列
     */
    public static function getVoiceBoxLogQueue()
    {
        $ret     = $upAry = [];
        $db_name = 'log_push_voicebox';

        $data = DB::table($db_name)->where('is_up','N')->where('ip','!=','null')->whereRaw('created_at >= DATEADD(hh,  -8,GETDATE())');
        if($data->count())
        {
            $listAry = $data->get();
            foreach ($listAry as $val)
            {
                if(strlen($val->ip))
                {
                    $tmp = [];
                    $tmp['ip']    = $val->ip;
                    $tmp['action']= $val->action;
                    $tmp['tax']   = $val->tax_num;
                    $tmp['local'] = $val->b_factory_a_id;
                    $ret[]        = $tmp;
                    //被取用的ＩＤ
                    $upAry[] = $val->id;
                }
            }
            //2. 更新回資料庫，已被取用
            LogLib::updateVoiceBoxLogQueue($upAry);
        }

        return $ret;
    }
    /**
     * 回船已取得 場地音訊盒佇列
     */
    public static function updateVoiceBoxLogQueue($idAry)
    {
        $db_name = 'log_push_voicebox';
        $INS = array();
        $INS['is_up']     = 'R';
        $INS['up_stamp']  = SHCSLib::getNow();

        return DB::table($db_name)->whereIn('id',$idAry)->update($INS);
    }
    /**
     * 場地音訊盒柱列
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putPushVoiceBoxLog($factory_a_id, $ip ,$b_supply_id,$tax_num , $push_type,$action = '')
    {
        if(!$factory_a_id || !$ip) return false;
        $db_name = 'log_push_voicebox';
        //record
        $INS = array();
        $INS['is_up']         = 'N';
        $INS['b_factory_a_id']= $factory_a_id;
        $INS['b_supply_id']   = $b_supply_id;
        $INS['tax_num']       = $tax_num;
        $INS['ip']            = $ip;
        $INS['action']        = $action;
        $INS['type']          = $push_type;
        $INS['created_at']    = date('Y-m-d H:i:s');
        //新增
        return DB::table($db_name)->insertGetId($INS);
    }
    /**
     * 變更 訪客出借紀錄　ＡＰＲＯＣ（Ａ借出Ｐ進廠Ｒ出廠Ｏ還卡Ｃ遺失）
     * @param $uid
     * @return int
     */
    public static function setGuestRecord($guest_id,$door_type)
    {
        $isUp = $ret  = 0;
        $UPD  = [];
        $log  = DB::table('b_guest')->select('aproc')->whereIn('aproc',['A','P','R'])->where('id',$guest_id)->first();
        if(isset($log->aproc))
        {
            $aproc = $log->aproc;

            if($door_type == 1 && in_array($aproc,['A','R']))
            {
                $isUp++;
                $UPD['aproc'] = 'P';
            }
            if($door_type == 2 && in_array($aproc,['A','P']))
            {
                $isUp++;
                $UPD['aproc'] = 'R';
            }
            if($isUp){
                $UPD['updated_at'] = SHCSLib::getNow();
                $ret = DB::table('b_guest')->where('id',$guest_id)->update($UPD);
            }
        }

        return $ret;
    }

    /**
     * 推播柱列
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putPushQueueLog($uid, $channelId , $push_type,$push_title, $push_body)
    {
        if(!$uid || !$channelId) return false;
        $db_name = 'log_push_queue';
        //record
        $INS = array();
        $INS['is_up']         = 'N';
        $INS['b_cust_id']     = $uid;
        $INS['push_token']    = $channelId;
        $INS['push_title']    = $push_title;
        $INS['push_body']     = $push_body;
        $INS['push_type']     = $push_type;
        $INS['created_at']    = date('Y-m-d H:i:s');
        //新增
        return DB::table($db_name)->insertGetId($INS);
    }

    /**
     * 紀錄 列印鎖卡
     */
    public static function createLogPairCardLock($supply_id,$uid,$mod_user)
    {
        $db_name = 'log_paircard_lock';
        $now     = date('Y-m-d H:i:s');
        $INS = array();
        $INS['b_supply_id'] = $supply_id;
        $INS['b_cust_id']   = $uid;
        $INS['new_user']    = $mod_user;
        $INS['mod_user']    = $mod_user;
        $INS['created_at']  = $now;
        $INS['updated_at']  = $now;

        return DB::table($db_name)->insertGetId($INS);
    }
    /**
     * 解除 列印鎖卡
     */
    public static function setLogPairCardLock($supply_id,$uid,$lockStatu,$mod_user)
    {
        $db_name = 'log_paircard_lock';
        $now     = date('Y-m-d H:i:s');

        $INS = DB::table($db_name)->where('b_supply_id',$supply_id)->where('b_cust_id',$uid);
        if($INS->count())
        {
            return $INS->update(['isLock'=>$lockStatu,'mod_user'=>$mod_user,'updated_at'=>$now]);
        } else {
            return LogLib::createLogPairCardLock($supply_id,$uid,$mod_user);
        }
    }


    /**
     * 紀錄 推播佇列
     */
    public static function getLogQueue()
    {
        $ret     = $upAry = $cancelAry = [];
        $db_name = 'log_push_queue';

        $data = DB::table($db_name)->where('is_up','N')->where('push_token','!=','null')->take(2000);
        if($data->count())
        {
            $listAry = $data->get();
            foreach ($listAry as $val) {
                if (strlen($val->push_token)) {
                    if (time() - strtotime($val->created_at) > 5 * 60 * 60) { // 若推播建立時間超過 5 小時，則記錄為逾時
                        $cancelAry[] = $val->id;
                    } else {
                        $tmp = [];
                        $tmp['token'] = $val->push_token;
                        $tmp['title'] = $val->push_title;
                        $tmp['body']  = $val->push_body;
                        $ret[] = $tmp;
                        //被取用的ＩＤ
                        $upAry[] = $val->id;
                    }
                }
            }
            //2. 更新回資料庫，已被取用
            LogLib::updateLogQueue($upAry);
            LogLib::updateLogQueue($cancelAry, 'C');
        }

        return $ret;
    }
    /**
     * 紀錄 推播佇列
     */
    public static function updateLogQueue($idAry, $isUp = 'R')
    {
        if (empty($idAry)) return 0;
        $db_name = 'log_push_queue';
        $INS = array();
        $INS['is_up']     = $isUp;
        $INS['up_stamp']  = SHCSLib::getNow();

        return DB::table($db_name)->whereIn('id', $idAry)->update($INS);
    }
    /**
     * 取得特定時間內的刷卡紀錄
     * @param $b_cust_id
     * @param $last_time
     * @return bool
     */
    public static function getRepeatDoor($b_factory_id,$b_factory_d_id, $isUser , $b_cust_id,$last_time)
    {
        if(!$b_cust_id) return [0,1,'N',''];

        $db_name = ($isUser)? (($isUser == 2)? 'log_door_inout_guest' : 'log_door_inout') : 'log_door_inout_car';
        $db_key  = ($isUser)? (($isUser == 2)? 'b_guest_id' : 'b_cust_id') : 'b_car_id';

        $data = DB::table($db_name)->where('b_factory_id',$b_factory_id)->where('b_factory_d_id',$b_factory_d_id)->where('created_at','>=',$last_time);
        $data = $data->where($db_key,$b_cust_id);
        $data = $data->first();
        return isset($data->id)? [$data->id,$data->door_type,$data->door_result,$data->door_memo] : [0,1,'N',''];
    }

    /**
     * 取得 該門禁紀錄之工程身份類型
     * @param $log_id
     * @return string
     */
    public static function getJobKind($log_id,$type = 'M')
    {
        if(!$log_id) return '';
        $table = ($type == 'C')? 'log_door_inout_car' : 'log_door_inout';
        $data = DB::table($table)->where('id',$log_id)->select('job_kind')->first();
        return isset($data->job_kind)? $data->job_kind : '';
    }

    /**
     * 取得 該門禁紀錄之工作許可証ID
     * @param $log_id
     * @return string
     */
    public static function getLogDoorWorkID($log_id)
    {
        if(!$log_id) return 0;
        $data = DB::table('log_door_inout')->where('id',$log_id)->select('wp_work_id')->first();
        return isset($data->wp_work_id)? $data->wp_work_id : 0;
    }

    /**
     * 取得 該門禁紀錄之圖片
     * @param $log_id
     * @return string
     */
    public static function getLogDoorImgUrl($log_id,$type = 'M')
    {
        if(!$log_id) return '';
        $table = ($type == 'C')? 'log_door_inout_car' : 'log_door_inout';
        $data = DB::table($table)->where('id',$log_id)->select('id','img_path')->first();
        $imgPath = isset($data->img_path)? $data->img_path : '';
//        dd($log_id,$imgPath,$table,$data);
        if(substr($imgPath,0,4) != 'http'){
            $imgPath = url('img/Door/'.SHCSLib::encode($log_id).'?type='.$type);
        }
        return $imgPath;
    }

    /**
     * 請求發送認證 紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putPushLog($uid = '', $channelId = 0, $type = 0, $message = '', $rep = array(), $result = 0)
    {
        if(!$uid || !$channelId) return false;
        //record
        $INS = array();
        $INS['b_cust_id']    = $uid;
        $INS['pusher_id']    = $channelId;
        $INS['type']         = $type;
        $INS['rept']         = ($result)? 'Y' : 'N';
        $INS['message']      = json_encode($message);
        $INS['rep_id']       = (isset($rep['multicast_id']))?   $rep['multicast_id']   : 0;
        $INS['rep_time']     = (isset($rep['success']))? $rep['success'] : 0;
        $INS['err_code']     = (isset($rep['failure']))? $rep['failure'] : 0;
        $INS['err_msg']      = (isset($rep['results']))? json_encode($rep['results'])  : '';
        $INS['created_at']    = date('Y-m-d H:i:s');
        //新增
        return DB::table('log_push')->insertGetId($INS);
    }

    /**
     * 取得 門禁工作站之白名單更新之當日最後一筆紀錄 ＆ 新增紀錄
     * @param $uid
     * @return int
     */
    public static function chkLogDoorHeadUp($b_factory,$b_factory_a,$ip)
    {
        $today = date('Y-m-d');
        //1. 搜尋本日 更新紀錄（img_at）
        $log = DB::table('log_door_head_at')->where('b_factory_id',$b_factory)->where('b_factory_a_id',$b_factory_a)->
                where('update_date',$today)->orderby('id','desc')->select('img_at')->first();
        $ret = isset($log->img_at)? $log->img_at : 0;

        //2. 記錄教育訓練通過
        LogLib::createLogDoorHeadUp($b_factory,$b_factory_a,$ip,time());

        return $ret;
    }

    /**
     * 紀錄 門禁工作站之白名單更新 紀錄
     * @param $uid
     * @return int
     */
    public static function createLogDoorHeadUp($b_factory,$b_factory_a,$ip,$img_at)
    {
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        // 記錄教育訓練通過
        $INS = [];
        $INS['b_factory_id']    = $b_factory;
        $INS['b_factory_a_id']  = $b_factory_a;
        $INS['ip']              = $ip;
        $INS['update_date']     = $today;
        $INS['img_at']          = $img_at;
        $INS['created_at']      = $now;

        return DB::table('log_door_head_at')->insertGetId($INS);
    }

    /**
     * 取得 該承攬商 非負責人的施工人員
     * @param $uid
     * @return int
     */
    public static function getSupplyＷorkers($sid,$adUser = [])
    {
        $data  = DB::table('view_door_supply_whitelist_pass')->select('b_cust_id')->where('b_supply_id',$sid)->whereNotIn('b_cust_id',$adUser)->get();
        return (array) $data;
    }

    /**
     * 取得 當日進出紀錄_錯誤紀錄
     * @param int $level
     * @param $pid
     * @param string $door_date
     * @param int $store
     * @param int $err_code
     * @return mixed
     */
    public static function getTodayInputErrLog($mode = 'M',$store_id = 0,$door_id = 0,$e_project_id = 0,$supply_id = 0,$door_date = '',$err_code = 0,$level = 1)
    {
        if(!$door_date) $door_date = date('Y-m-d');
        $table = ($mode == 'C')? 'log_door_inout_car' : 'log_door_inout';
        $user  = ($mode == 'C')? 'log_door_inout.car_no as name' : 'log_door_inout.name';
        $data = DB::table($table)->join('b_factory as s','s.id','=','log_door_inout.b_factory_id')->
                join('b_factory_d as d','d.id','=','log_door_inout.b_factory_d_id')->
                where('log_door_inout.door_date',$door_date);

        if($store_id)
        {
            $data = $data->where('log_door_inout.b_factory_id',$store_id);
        }
        if($door_id)
        {
            $data = $data->where('log_door_inout.b_factory_d_id',$door_id);
        }
        if($e_project_id)
        {
            $data = $data->where('log_door_inout.e_project_id',$e_project_id);
        }
        if($supply_id)
        {
            $data = $data->where('log_door_inout.b_supply_id',$supply_id);
        }
        if($err_code)
        {
            $data = $data->where('log_door_inout.err_code',$err_code);
        } else {
            $data = $data->where('log_door_inout.err_code','!=', 0);
        }
//        dd($level,$store_id,$door_id,$supply_id,$err_code,$door_date,$data->get());
        if($level == 1)
        {
            $data = $data->selectRaw('s.name as store,d.name as door,log_door_inout.e_project_id,
            COUNT(log_door_inout.id) as amt')->groupby('s.name','d.name','e_project_id');
        } else {
            $data = $data->select('s.name as store','d.name as door','log_door_inout.unit_name',
                'log_door_inout.e_project_id','log_door_inout.door_type','log_door_inout.door_stamp',
                'log_door_inout.door_memo',$user);
        }
        return  $data->get();
    }

    /**
     * 取得 該成員 今日進出紀錄
     * @param $uid
     * @return int
     */
    public static function getTodayInputLog($uid,$store , $door_date = '',$isReplayAry = 1)
    {
        if(!$door_date) $door_date = date('Y-m-d');
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');

        $data  = DB::table('log_door_inout')->where('b_cust_id',$uid)->where('door_date',$door_date);
        $data  = $data->where('b_factory_id',$store);
        $data  = $data->orderby('door_stamp','desc')->get();
        if(!$isReplayAry)
        {
            if(count($data))
            {
                $ret = [];
                foreach ($data as $key => $val)
                {
                    $door_type   = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                    $door_result = isset($doorResultAry[$val->door_result])? $doorResultAry[$val->door_result] : '';
                    $door        = b_factory::getName($val->b_factory_id);
                    $logStrAry   = ['door_result'=>$door_result,'door_stamp'=>$val->door_stamp,'door_type'=>$door_type,'door'=>$door];
                    $ret[$key]   = Lang::get('sys_base.base_30105',$logStrAry);
                }
            }
        } else {
            $ret = $data;
        }

        return $ret;
    }

    /**
     * 取得 該會員當日最後登入一筆紀錄
     * @param $uid
     * @return int
     */
    public static function getLastInOutTypeLog($b_factory_id,$uid,$isUser = 1,$door_date = '')
    {
        //搜尋人員
        $table = ($isUser)? (($isUser == 2)? 'view_log_door_today_guest' : 'view_log_door_today') : 'view_log_door_today_car';

        $data  = DB::table($table)->join('b_factory_d as d','d.id','=',$table.'.b_factory_d_id')->
        where($table.'.b_factory_id',$b_factory_id)->whereIn($table.'.door_type',[1,2]);

        $data = $data->where($table.'.unit_id',$uid);

        $data  = $data->select($table.'.door_type','d.id','d.name')->
        orderby($table.'.door_stamp','desc')->where($table.'.door_result','Y')->first();
        $ret = (isset($data->door_type))? [$data->id,$data->name,$data->door_type] : [0,'',0];
//        dd($b_factory_id,$uid,$isUser,$data,$table,$ret);
        return $ret;
    }

    /**
     * 取得 該會員 指定門禁紀錄
     * @param $uid
     * @return int
     */
    public static function getInOutLog($isUser = 1, $uid,$today, $b_factory_id, $b_factory_d_id,$isOnline, $isData = 'N')
    {
        $door_memo  = '';
        $table_name = ($isUser)? (($isUser == 2)? 'log_door_inout_guest' : 'log_door_inout') : 'log_door_inout_car';
        $user_name  = ($isUser)? (($isUser == 2)? 'b_guest_id' : 'b_cust_id') : 'b_car_id';

        $data  = DB::table($table_name)->where($user_name,$uid)->where('door_stamp',$today)->where('b_factory_id',$b_factory_id);
        $data  = $data->where('b_factory_d_id',$b_factory_d_id);
        if($isOnline)
        {
            $data = $data->where('isOnline',$isOnline);
        }
        $data  = $data->first();

        if(isset($data->id))
        {
            //5-1. 參數
            $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
            $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');
            $door_type      = $data->door_type;
            $door_result    = $data->door_result;
            $name           = ($isUser)? $data->name : $data->car_no;
            $violation_memo = $data->door_memo;
            $b_supply       = $data->unit_name;
            $project        = ($isUser == 2)? $data->job_kind : e_project::getName($data->e_project_id);

            //5-2-1. 進出模式文字
            $door_type_name = isset($doorTypeAry[$door_type])? $doorTypeAry[$door_type] : Lang::get('sys_base.base_30164');
            //5-2-2. 進出結果文字
            $door_result_name = isset($doorResultAry[$door_result])? $doorResultAry[$door_result] : Lang::get('sys_base.base_30151');
            //5-2-3. 人/車
            $strcode = ($isUser)? 'base_30102' : 'base_30106';

            //5-3. 組合文字
            $door_memo1     = Lang::get('sys_base.base_30100',['door_result'=>$door_result_name,'door_stamp'=>$today]);
            $door_memo2     = Lang::get('sys_base.'.$strcode ,['name'=>$name,'supply'=>$b_supply,'project'=>$project]);
            $door_memo3     = Lang::get('sys_base.base_30103',['door_type'=>$door_type_name]);
            $door_memo4     = $violation_memo ? Lang::get('sys_base.base_30104',['name'=>$name,'door_type'=>$door_type_name,'memo'=>$violation_memo]) : '';

            $door_memo = $door_memo2.$door_memo3.$door_memo1.$door_memo4;
            //2019-11-08 配合昱俊
            $door_violation2    = $violation_memo ? Lang::get('sys_base.base_30117',['name'=>$name,'door_type'=>$door_type_name,'memo'=>$violation_memo]) : '';

            $resultAry = [];
            if($door_result == 'N')
            {
                $resultAry[] = ['',''];
                $resultAry[] = [Lang::get('sys_base.base_30116'),$door_violation2];
            } else {
                $resultAry[] = [Lang::get('sys_base.base_30112'),$b_supply];
                $resultAry[] = [Lang::get('sys_base.base_30113'),$name];
                $resultAry[] = [Lang::get('sys_base.base_30114'),$project];
                $resultAry[] = [Lang::get('sys_base.base_30115'),$door_type_name];
                $resultAry[] = [Lang::get('sys_base.base_30110'),$today];
                $resultAry[] = [Lang::get('sys_base.base_30111'),$door_result_name];
            }
        }
        //dd([$data,$door_memo]);
        return (isset($data->id))? ($isData == 'Y' ? [$data->id,$door_result,$door_memo,$door_type,$resultAry] : $data->id) : 0;
    }

    public static function checkLastLog($isUser = 1, $uid,$today, $b_factory_id, $b_factory_d_id)
    {
        $table_name = ($isUser)? (($isUser == 2)? 'log_door_inout_guest' : 'log_door_inout') : 'log_door_inout_car';
        $user_name  = ($isUser)? (($isUser == 2)? 'b_guest_id' : 'b_cust_id') : 'b_car_id';

        $data  = DB::table($table_name)->where('door_stamp','>',$today)->where('b_factory_id',$b_factory_id);
        $data  = $data->where('b_factory_d_id',$b_factory_d_id);
        if($isUser == 2)
        {
            $data  = $data->where('rfid_code',$uid);
        } else {
            $data  = $data->where($user_name,$uid);
        }
        $data  = $data->select('id')->orderby('id','desc')->first();

        return isset($data->id)? $data->id : 0;
    }

    /**
     * 設定 該會員進出紀錄 拒絕
     * @param $uid
     * @return int
     */
    public static function setInOutRejectLog($logid, $uid = 0,$door_stamp = '')
    {
        $isUp  = 0;
        $now   = date('Y-m-d H:i:s');
        $data  = DB::table('log_door_inout');
        if($logid)
        {
            $isUp++;
            $data->where('id',$logid);
        } elseif(!$logid && $uid && $door_stamp)
        {
            $isUp++;
            $data->where('b_cust_id',$uid)->where('door_stamp',$door_stamp);
        }

        return ($isUp)? $data->update(['door_type'=>3,'updated_at'=>$now]) : 0;
    }

    /**
     * 新增 門禁進出 紀錄
     * @return mixed
     */
    public static function putInOutLog($record,$img = '',$errCode = '',$isOnline = 'N',$isOver = 'N')
    {
        if(!is_array($record) || count($record) != 16) return 0;
        list($b_cust_id,$name,$bc_type,$unit_id,$unit_name,$b_rfid_id,$rfid_code,$door_type,$door_stamp,$b_factory_id,$b_factory_d_id,$e_project_id,$door_result,$door_memo,$jobkind,$wp_work_id) = $record;
        // 記錄排程紀錄

        $INS = [];
        //車輛紀錄
        if($bc_type == 999)
        {
            $tableName = 'log_door_inout_car';
            $INS['b_car_id']        = $b_cust_id;
            $INS['car_no']          = $name;
            $INS['b_supply_id']     = $unit_id;
            $INS['e_project_id']    = $e_project_id;
            $INS['wp_work_id']      = $wp_work_id;
        } //訪客
        elseif($bc_type == 4)
        {
            $tableName = 'log_door_inout_guest';
            $INS['b_guest_id']      = $b_cust_id;
        } else {
            //人員紀錄
            $tableName = 'log_door_inout';
            $INS['b_cust_id']       = $b_cust_id;
            $INS['name']            = $name;
            if($bc_type == 2)
            {
                $INS['be_dept_id']      = $unit_id;
            } elseif($bc_type == 3) {
                $INS['b_supply_id']     = $unit_id;
                $INS['e_project_id']    = $e_project_id;
            }
            $INS['wp_work_id']      = $wp_work_id;
        }

        $INS['bc_type']         = $bc_type;
        $INS['job_kind']        = $jobkind;
        $INS['unit_name']       = $unit_name;
        $INS['b_rfid_id']       = $b_rfid_id;
        $INS['rfid_code']       = $rfid_code;
        $INS['door_date']       = substr($door_stamp,0,10);
        $INS['door_type']       = $door_type;
        $INS['door_stamp']      = $door_stamp;
        $INS['b_factory_id']    = $b_factory_id;
        $INS['b_factory_d_id']  = $b_factory_d_id;
        $INS['door_result']     = $door_result;
        $INS['door_memo']       = $door_memo;
        $INS['isOnline']        = $isOnline;
        $INS['isOver']          = $isOver;
        $INS['err_code']        = $errCode;
        $INS['created_at']      = SHCSLib::getNow();
        $log_id = DB::table($tableName)->insertGetId($INS);
        //拍照圖片
        if( $log_id && is_string($img) && strlen($img) > 10)
        {
            if(substr($img,0,4) != 'http')
            {
                $filepath = config('mycfg.door_inout_path').date('Y/m/d/H/i/');
                $filename = $log_id.'_A.jpg';
                $imgUrl   = $filepath.$filename;
                SHCSLib::saveBase64ToImg($filepath,$filename,$img);
            } else {
                $imgUrl   = $img;
            }
            DB::table($tableName)->where('id',$log_id)->update(['img_path'=>$imgUrl]);
        }

        //dd([1,$log_id,$img,$INS]);
        // 記錄 Log
        return $log_id;
    }

    /**
     * 取得 今日白名單
     * @param $sid
     */
    public static function getWhiteListSelect($sid)
    {
        $ret = [];
        if(!$sid) return $ret;
        $data = DB::table('view_door_supply_whitelist_pass')->where('b_supply_id',$sid)->get();

        if(count($data))
        {
            foreach ( $data as $val)
            {
                $ret[] = $val->b_cust_id;
            }
        }
        return $ret;
    }

    /**
     * 取得 教育訓練通過名單
     * @param $sid
     */
    public static function getCoursePassSelect($sid)
    {
        $ret = [];
        if(!$sid) return $ret;
        $data = DB::table('log_course_pass')->join('view_door_supply_member as m','m.b_cust_id','=','log_course_pass.b_cust_id')->
                where('m.b_supply_id',$sid)->where('log_course_pass.sdate',date('Y-m-d'))->
                select('log_course_pass.b_cust_id')->get();

        if(count($data))
        {
            foreach ( $data as $val)
            {
                $ret[] = $val->b_cust_id;
            }
        }
        return $ret;
    }

    /**
     * 新增 教育訓練通過 紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putCoursePassLog($bid = 0 ,$edate = '')
    {
        if(!$bid) return false;
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        if(!$edate) $edate = $today;

        if(DB::table('log_course_pass')->where('b_cust_id',$bid)->where('sdate',$today)->count())
        {
            //$ret = LogLib::setCoursePassCloseLog($bid,'N');
            $ret = 1;
        } else {
            // 記錄教育訓練通過
            $INS = [];
            $INS['b_cust_id']   = $bid;
            $INS['sdate']       = $today;
            $INS['edate']       = $edate;
            $INS['created_at']  = $now;
            $ret = DB::table('log_course_pass')->insertGetId($INS);
        }

        // 記錄 Log
        return $ret;
    }

    /**
     * 新增 門禁工作站讀卡機上傳 紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putDoorReaderAliveLog($b_factory_id,$b_factory_d_id,$ip,$reader,$memo1 = '',$memo2 = '')
    {
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');

        // 記錄教育訓練通過
        $INS = [];
        $INS['b_factory_id']    = $b_factory_id;
        $INS['b_factory_d_id']  = $b_factory_d_id;
        $INS['ip']              = $ip;
        $INS['reader']          = $reader;
        $INS['sdate']           = $today;
        $INS['memo1']           = $memo1;
        $INS['memo2']           = $memo2;
        $INS['created_at']      = $now;
        $ret = DB::table('log_door_alive')->insertGetId($INS);

        // 記錄 Log
        return $ret;
    }
    /**
     * 關閉 教育訓練通過 紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function setCoursePassCloseLog($id = 0,$isClose = 'Y')
    {
        if(!$id) return false;
        // 關閉教育訓練通過
        $UPD = [];
        $UPD['isClose']     = $isClose;
        $UPD['updated_at']  = date('Y-m-d H:i:s');
        // 記錄 Log
        return DB::table('log_course_pass')->where('sdate', date('Y-m-d'))->where('b_cust_id', $id)->update($UPD);
    }
    /**
     * 新增 系統排程 紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putCronLog($type = '' ,$result = 'N', $reply = '' )
    {
        // 記錄排程紀錄
        $INS = [];
        $INS['cron_date']   = date('Y-m-d');
        $INS['cron_type']   = $type;
        $INS['cron_result'] = $result;
        $INS['cron_reply']  = $reply;
        $INS['created_at']  = date('Y-m-d H:i:s');

        // 記錄 Log
        return DB::table('log_cron')->insertGetId($INS);
    }
    /**
     * 紀錄 使用者帳號紀錄
     * @param $uid
     * @param $menu
     * @param $ip
     * @param $action
     * @param $model
     * @param $model_id
     * @return bool
     */
    public static function putLogAction($uid,$menu,$ip,$action,$model,$model_id)
    {
        $actionAry = ['1'=>'INSERT','2'=>'UPDATE'];
        $actionStr = isset($actionAry[$action])? $actionAry[$action] : '';
        if(!$uid || !$action ||!$model) return false;
        //
        $INS = array();
        $INS['sys_kind']        = config('mycfg.sys_kind');
        $INS['b_cust_id']       = $uid;
        $INS['ip']              = $ip;
        $INS['menu']            = $menu;
        $INS['action']          = $actionStr;
        $INS['model']           = $model;
        $INS['model_id']        = $model_id;
        $INS['created_at']      = date('Y-m-d H:i:s');

        return DB::table('log_action')->insertGetId($INS);
    }
    /**
     * 取得 上次的 成功/失敗 登入紀錄
     * @param int type 1:全部，2:登入成功，3:登入失敗
     */
    public static function getLastLoginLog($account,$type = 1)
    {
        $suc = $err = '';
        if($type == 1 || $type == 2)
        {
            $suc = DB::table('log_login')->where('account',$account)->where('is_suc','Y')->orderby('id','desc')->first();
        }
        if($type == 1 || $type == 3)
        {
            $err = DB::table('log_login')->where('account',$account)->where('is_suc','N')->orderby('id','desc')->first();
        }

        return ['suc'=>$suc,'err'=>$err];
    }
    /**
     * 取得 登入紀錄
     * @param int type 1:全部，2:登入成功，3:登入失敗
     */
    public static function getLoginLog($account,$limit = 1)
    {
        $ret = DB::table('log_login')->where('account',$account)->orderby('id','desc')->limit($limit)->get();

        return $ret;
    }
    /**
     * 新增 登入成功/失敗紀錄
     * @param string $account
     * @param string $ip
     * @param string $imei
     * @return mixed
     */
    public static function putLoginLog($isSuc = 0,$account = '', $ip = '', $imei = '', $isSource = 9)
    {
        //record
        $INS = array();
        $INS['account']       = $account;
        $INS['ip']            = $ip;
        $INS['imei']          = $imei;
        $INS['login_source']  = $isSource;
        $INS['is_suc']        = ($isSuc)? 'Y' : 'N';
        $INS['created_at']    = date('Y-m-d H:i:s');

        return DB::table('log_login')->insertGetId($INS);
    }
}
