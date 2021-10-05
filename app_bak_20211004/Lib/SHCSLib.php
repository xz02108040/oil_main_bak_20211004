<?php

namespace App\Lib;

use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_img;
use DB;
use Storage;
use Image;
use Lang;
use File;
use Session;
use DateTime;
use Intervention\Image\Exception\NotReadableException;

class SHCSLib {

    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'left';

    /**
     * @return datetime
     */
    public static function getNow()
    {
        $t      = microtime(true);
        $micro  = sprintf("%06d",($t - floor($t)) * 1000000);
        $d      = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
        return $d->format("Y-m-d H:i:s.v");
    }
    /**
     * 轉換　民國日期
     */
    public static function chgTaiwanDate($today) {
        $date = new \DateTime($today);
        $date->modify("-1911 year");
        return ltrim($date->format('Y-m-d'),"0");
    }
    /**
     * 今天星期幾
     * @param $sdate
     * @return false|string
     */
    public static function chgWeek($sdate) {
        $weekAry    = SHCSLib::getCode('WEEK',0);
        $week       = date('w',strtotime($sdate));
        return isset($weekAry[$week])? $weekAry[$week] : '';
    }
    /**
     * 取得日期區間內的　星期幾的日期　陣列
     * @param $sdate
     * @return false|string
     */
    public static function get_circle_week($sdate,$edate,$week = 0) {
        $ret        = [];
        $today      = date('Y-m-d');
        if(!$sdate) $sdate = $today;
        if(!$edate) $edate = $today;
        $eStamp     = strtotime($edate);

        $next_week  = $sdate;
        $nStamp     = strtotime($next_week);
        $start_week = date('w',$nStamp);
        if($start_week == $week) $ret[] = $next_week;
        do {
            $next_week  = SHCSLib::get_next_week($next_week,$week);
            $nStamp     = strtotime($next_week);
            $isNext     = ($nStamp <= $eStamp)? true : false;
            if($isNext) $ret[] = $next_week;
        }while($isNext);

        return $ret;
    }
    /**
     * 取得下次星期幾的日期
     * @param $sdate
     * @return false|string
     */
    public static function get_next_week($sdate,$week = 0) {
        $today      = date('Y-m-d');
        $weekAry    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        if(!$sdate) $sdate = $today;
        $thisWeek   = isset($weekAry[$week])? $weekAry[$week] : $weekAry[0];

        return date('Y-m-d',strtotime($sdate." next ".$thisWeek));
    }

    /**
     * 取得　本周開始日期＆本周結束日期＆上周開始日期＆上周結束日期
     * @param string $gdate
     * @param int $first
     * @return array
     */
    public static function aweek($gdate = "", $first = 0){
        if(!$gdate) $gdate = date("Y-m-d");
        $w = date("w", strtotime($gdate));//取得一周的第幾天,星期天開始0-6
        $dn = $w ? $w - $first : 6;//要減去的天數
        //本周開始日期
        $st = date("Y-m-d", strtotime("$gdate -".$dn." days"));
        //本周結束日期
        $en = date("Y-m-d", strtotime("$st +6 days"));
        //上周開始日期
        $last_st = date('Y-m-d',strtotime("$st - 7 days"));
        //上周結束日期
        $last_en = date('Y-m-d',strtotime("$st - 1 days"));
        return array($st, $en,$last_st,$last_en);//返回開始和結束日期
    }
    /**
     * @param $uri
     * @return string|string[]
     */
    public static function checkUriWrite($uri)
    {
        $WirteSessAry = Session::get('user.menu_wirte',[]);
        return (count($WirteSessAry) && isset($WirteSessAry[$uri]))? 'Y' : 'N';
    }

    /**
     * @param $uri
     * @return string|string[]
     */
    public static function getUri($uri)
    {
        if(strlen($uri) > 1)
        {
            $uri = str_replace(['List','Edit','Create','post','/{id}','new_'],['','','','','',''],$uri);//
        }
        return $uri;
    }

    /**
     * 產生時間格式
     * @param int $excelDateNum
     * @return false|string
     */
    public static function getHoursMinutes($seconds, $format = '%02d:%02d') {

        if (empty($seconds) || ! is_numeric($seconds) || !$seconds) {
            return '';
        }

        $minutes = round($seconds / 60);
        $hours = floor($minutes / 60);
        $remainMinutes = ($minutes % 60);

        return sprintf($format, $hours, $remainMinutes);
    }
    /**
     * 用來解決excel日期格式回傳數字型別問題
     * @param int $excelDateNum
     * @return false|string
     */
    public static function tranExcelDate($excelDateNum = 0)
    {
        $UNIX_DATE = ($excelDateNum - 25569) * 86400;
        return date("Y-m-d", $UNIX_DATE);
    }
    /**
     * 計算幾歲
     */
    public static function birthday($birthday){
        list($year,$month,$day) = explode("-",$birthday);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff  = date("d") - $day;
        if ($day_diff < 0 || $month_diff < 0)
            $year_diff--;
        return $year_diff;
    }
    /**
     * 計算兩個ＧＰＳ位置間的距離
     * @param $GPSX1
     * @param $GPSY1
     * @param $GPSX2
     * @param $GPSY2
     * @return float|int
     */
    public static function getGPSDistance($GPSX1,$GPSY1,$GPSX2,$GPSY2, $show = 1){

        $radLat1 = deg2rad($GPSX1);
        $radLat2 = deg2rad($GPSX2);
        $radLng1 = deg2rad($GPSY1);
        $radLng2 = deg2rad($GPSY2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        //回傳 公尺
        $distance =  2 * asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;

        return ($show == 2)? round($distance,2) : $distance;
    }

    /**
     * 轉換圖片成base64格式
     * @param $img_type
     * @param $id
     * @param int $reszie
     * @return array|string
     */
    public static function toImgBase64String($img_type,$id,$reszie = 0)
    {
        $defImg  = public_path('images/photo_null.png');
        $imgPath = '';
        switch ($img_type)
        {
            case 'user':
                $imgPath = b_cust_a::getHeadImg($id);
                break;
            case 'car':
                $imgPath = b_car::getImg($id,'A');
                break;
            case 'door_user':
                $data = DB::table('log_door_inout')->where('id',$id)->select('img_path')->first();
                $imgPath = isset($data->img_path)? $data->img_path : '';
                break;
            case 'door_car':
                $data = DB::table('log_door_inout_car')->where('id',$id)->select('img_path')->first();
                $imgPath = isset($data->img_path)? $data->img_path : '';
                break;
            case 'permit':
                $imgPath = wp_work_img::getImg($id);
                break;
        }
        if(strpos($imgPath,'http') !== false)
        {
            $ret = $imgPath;
        } else {
            $filepath = ($imgPath)? storage_path('app'.$imgPath) : $defImg;
            if(!File::exists($filepath)) return $filepath;
            $show = ($id == 14349)? 1 : 0;
            $ret = SHCSLib::tranImgToBase64Img($filepath,$reszie,$show);
        }
        return $ret;
    }
    /**
     * 取得工作許可證 各進度的顏色
     * @return array
     */
    public static function tranImgToBase64Img($filepath,$reszie = 0,$show = 0)
    {
        try
        {
            $image = Image::make($filepath);

        }
        catch(NotReadableException $e)
        {
            $image = Image::make(public_path('images/photo_null.png'));
        }
        if($reszie > 0)
        {
            return (string)$image->resize($reszie,null,function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url','75');
        } else {
            return (string)$image->encode('data-url','75');
        }

    }
    /**
     * 取得工作許可證 各進度的顏色
     * @return array
     */
    public static function getPermitAprocColor()
    {
        return ['A'=>5,'B'=>0,'P'=>4,'K'=>7,'R'=>2,'O'=>9,'F'=>10,'C'=>0];
    }

    public static function isPermitCheckOverLimit($recordName , $val)
    {
        $ret = true;
        if(!$recordName || !$val) return $ret;
        $limitAry = wp_check_topic_a::getLimitRange(3);
        $record = isset($limitAry[$recordName])? $limitAry[$recordName] : '';
        if(!$record) return $ret;

        $action         = isset($record['safe_action'])? $record['safe_action'] : '';
        $safe_limit1    = isset($record['safe_limit1'])? $record['safe_limit1'] : '';
        $safe_limit2    = isset($record['safe_limit2'])? $record['safe_limit2'] : '';

        if($action == 'between')
        {
            if( floatval($safe_limit1) > floatval($val) || floatval($safe_limit2) < floatval($val))
            {
                $ret = false;
            }
        }
        if($action == 'under')
        {
            if(floatval($safe_limit1) <= floatval($val))
            {
                $ret = false;
            }
        }
        if($action == 'down')
        {
            if( floatval($safe_limit2) >= floatval($val))
            {
                $ret = false;
            }
        }

        return $ret;
    }

    public static function genReportAnsHtml($result,$len = 1)
    {
        return SHCSLib::mb_str_pad($result,$len);
    }
    public static function mb_strlen($input)
    {
        return (strlen($input) + mb_strlen($input,'UTF8')) / 2;
    }

    public static function mb_str_pad($input , $pad_length ,$pad_string = '&nbsp;' ){
        $strlen = SHCSLib::mb_strlen($input);
        if($strlen < $pad_length){
            $difference = $pad_length - $strlen;
            $left = ceil($difference / 2);
            $right = $difference - $left;
            return str_repeat($pad_string, $left) . $input . str_repeat($pad_string, $right);
        }else{
            return $input;
        }
    }

    public static function genTimeAry($rang = '23:59',$unit = '30',$isApi = 0)
    {
        $ret = [];
        $now   = time();
        $nowH  = date('H');
        $nowM  = '';
        $etime = ($rang)? strtotime(date('Y-m-d ').$rang) : strtotime('23:59');

        //1. 先找出離最近的時間
        $maxCount = floor(60/$unit);
        $maxSelectCount = $maxCount*24;

        for($i = 1;$i <=$maxCount; $i++)
        {
            $addnum = $unit * $i;
            if($addnum >= 60)
            {
                $nowH += 1;
                $nowM = '00';
            }
            $stime = strtotime(date($nowH.":".$nowM));
            if($stime > $now)
            {
                $stime = date('H:i',$stime);
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']      = $stime;
                    $tmp['name']    = $stime;
                    $ret[] = $tmp;
                } else {
                    $ret[$stime] = $stime;
                }
                break;
            }
        }

        //2. 在利用該時間，產生陣列
        for($i = 1; $i < $maxSelectCount ; $i++)
        {
            $addnum = $unit * $i;
            $time = date("Y-m-d H:i",strtotime($stime. " +".$addnum." min"));
            if(strtotime($time) < $etime)
            {
                $time = substr($time,11,5);
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']      = $time;
                    $tmp['name']    = $time;
                    $ret[] = $tmp;
                } else {
                    $ret[$time] = $time;
                }
            } else {
                break;
            }
        }
        //
        return $ret;
    }

    public static function genAllTimeAry($range = '',$rangs = '00:00',$unit = '30',$isApi = 0)
    {
        $ret = [];
        $etime = ($range)? strtotime(date('Y-m-d ').$range) : strtotime('23:59');
        $stime = $rangs;
        if($stime)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']      = $stime;
                $tmp['name']    = $stime;
                $ret[] = $tmp;
            } else {
                $ret[$stime] = $stime;
            }
        } else {

            $now   = time();
            $nowH  = date('H');
            $nowM  = '';
            $maxCount = floor(60/$unit);
            for($i = 1;$i <=$maxCount; $i++)
            {
                $addnum = $unit * $i;
                if($addnum >= 60)
                {
                    $nowH += 1;
                    $nowM = '00';
                }
                $stime = strtotime(date($nowH.":".$nowM));
                if($stime > $now)
                {
                    $stime = date('H:i',$stime);
                    if($isApi)
                    {
                        $tmp = [];
                        $tmp['id']      = $stime;
                        $tmp['name']    = $stime;
                        $ret[] = $tmp;
                    } else {
                        $ret[$stime] = $stime;
                    }
                    break;
                }
            }
        }
        //原限制只有99，10分鐘間距會不夠展開，調整為150正常顯示
        $maxSelectCount = 150;


        //2. 在利用該時間，產生陣列
        for($i = 1; $i < $maxSelectCount ; $i++)
        {
            $addnum = $unit * $i;
            $time = date("Y-m-d H:i",strtotime($stime. " +".$addnum." min"));
            if(strtotime($time) < $etime)
            {
                $time = substr($time,11,5);
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']      = $time;
                    $tmp['name']    = $time;
                    $ret[] = $tmp;
                } else {
                    $ret[$time] = $time;
                }
            } else {
                break;
            }
        }
        //3.
        return $ret;
    }

    /**
     * 計算時間差 回傳分鐘
     * @param $stime
     * @param $etime
     * @return float|int
     */
    public static function getTime($stime, $etime, $type = 1)
    {
        $ret = 0;
        if(!$stime || !$etime || is_null($stime) || is_null($etime)) return $ret;
        $cle    = abs(strtotime($etime) - strtotime($stime));

        if($type == 3)
        {
            $i = floor($cle / 60);
            $s = $cle - ($i * 60);
            $ret = sprintf('%02d:%02d',$i,$s);
        }
        elseif($type == 2)
        {
            $ret = floor($cle / 60);
        } else {
            $ret = $cle;
        }
        return $ret;
    }







    /**
     * [工作許可證使用] 產生此對象是否有資格執行，以及所擁有身份陣列
     * @param $b_cust_id
     * @param $bc_type
     * @param $workerAry
     * @param $target
     * @param $supply_worker
     * @param $supply_safer
     * @param $dept1
     * @param $dept2
     * @param $dept3
     * @param $dept4
     * @return array
     */
    public static function genPermitSelfTarget($b_cust_id,$bc_type,$supply_worker,$supply_safer,$dept1,$dept2,$dept3,$dept4,$dept5,$isApi = 0)
    {
        $myAppType  = [];
        //承攬商
        if($bc_type == 3) {
            //權限：該工作許可證指定之工負
            if($supply_worker == $b_cust_id)
            {
                $myAppType[] = 3;
                $myAppType[] = 4;
                $myAppType[] = 6; //全部
            }
            //權限：該工作許可證指定之工安
            if($supply_safer == $b_cust_id)
            {
                $myAppType[] = 3;
                $myAppType[] = 5;
                $myAppType[] = 6; //全部
            }
        }
        //職員
        else {
            //身份：轄區部門＆監造部門
            $dept       = view_dept_member::getDept($b_cust_id);
            $isDept1    = ($dept == $dept1)? 1 : 0;
            $isDept2    = ($dept == $dept2)? 1 : 0;
            $isDept3    = ($dept == $dept3)? 1 : 0;
            $isDept4    = ($dept == $dept4)? 1 : 0;
            $isDept5    = ($dept == $dept5)? 1 : 0;
            //權限：監造部門
            if($isDept2)
            {
                $myAppType[] = 6; //全部
                $myAppType[] = 1;
                $myAppType[] = 9;
            }
            //權限：轄區部門
            elseif($isDept1)
            {
                $myAppType[] = 6;
                $myAppType[] = 2;
                $myAppType[] = 9;
            }
            //權限：監工部門
            elseif($isDept3)
            {
                $myAppType[] = 6;
                $myAppType[] = 7;
                $myAppType[] = 9;
            }
            //權限：會簽部門
            elseif($isDept4)
            {
                $myAppType[] = 6;
                $myAppType[] = 8;
                $myAppType[] = 9;
            }
            //權限：轄區部門之上層部門
            elseif($isDept5)
            {
                $myAppType[] = 6;
                $myAppType[] = 10;
                $myAppType[] = 9;
            }
        }
        if($isApi && count($myAppType))
        {
            $tmpMyAppType = [];
            foreach ($myAppType as $val)
            {
                $tmp = [];
                $tmp['id'] = $val;
                $tmpMyAppType[] = $tmp;
            }
            $myAppType = $tmpMyAppType;
        }
        return $myAppType;
    }
    /**
     * 「工作許可證使用」- 是/否/無關
     * @param int $type
     * @return array
     */
    public static function genYNApiAry($type = 1)
    {
        $ret   = [];
        $ret[] = ['id'=>'', 'name'=>Lang::get('sys_base.base_10015')];
        $ret[] = ['id'=>'Y','name'=>Lang::get('sys_base.base_40224')];
        $ret[] = ['id'=>'N','name'=>Lang::get('sys_base.base_40225')];
        if($type)
        {
            $ret[] = ['id'=>'=','name'=>Lang::get('sys_base.base_40226')];
        }
        return $ret;
    }

    /**
     * 取得特定一週的起訖
     * @param string $gdate
     * @param int $first
     * @return array
     */
    public static function getWeek($gdate = "", $first = 1){
        if(!$gdate) $gdate = date("Y-m-d");
        $w = date("w", strtotime($gdate));//取得一周的第幾天,星期天開始0-6
        $dn = $w ? $w - $first : 6;//要減去的天數
        //本周開始日期
        $st = date("Y-m-d", strtotime("$gdate -".$dn." days"));
        //本周結束日期
        $en = date("Y-m-d", strtotime("$st +6 days"));
        //上周開始日期
        //$last_st = date('Y-m-d',strtotime("$st - 7 days"));
        //上周結束日期
        //$last_en = date('Y-m-d',strtotime("$st - 1 days"));
        return array($st, $en);//返回開始和結束日期
    }

    /**
     * 計算年紀
     * @param $birth
     * @return false|int|string
     */
    public static function toAge($birth)
    {
        $age = 0;
        if(!$birth || !CheckLib::isDate($birth)) return $age;

        list($by,$bm,$bd) = explode('-',$birth);
        $cm     =   date('n');
        $cd     =   date('j');
        $age    =   date('Y')- $by -1;
        if ($cm > $bm || $cm == $bm && $cd > $bd) $age++;
        return $age;
    }

    /**
     * 陣列去除多餘的數值
     * @param $srcAry
     * @param array $extAry
     * @return array
     */
    public static function array_key_exclude($srcAry ,$extAry = [])
    {
        if(!is_array($srcAry) || !is_array($extAry)) return $srcAry;
        if(!count($srcAry) || !count($extAry)) return $srcAry;

        foreach ($srcAry as $key =>$val)
        {
            if(in_array($key,$extAry)) unset($srcAry[$key]);
        }
        return $srcAry;
    }

    /**
     * 轉換圖片大小
     * @param $ImgPath
     * @param $ImgData
     * @param int $imgWidth
     * @param int $imgHeight
     * @return int
     */
    public static function tranImgSize($ImgPath,$ImgData,$imgWidth = 640,$imgHeight = 360,$resizeType = 1,$Orientation = 0)
    {
        $ret = 0;
        if(!$ImgData || !$ImgData) return 0;

        $img = Image::make($ImgData);

        if($Orientation)
        {
            //判斷角度翻轉
            switch($Orientation) {
                case 8:
                    $img = $img->rotate(90);
                    break;
                case 3:
                    $img = $img->rotate(180);
                    break;
                case 6:
                    $img = $img->rotate(-90);
                    break;
            }
        }
        //取得 轉換圖片後的內容
        if($resizeType == 2)
        {
            $img = $img->resize($imgWidth,$imgHeight);
        } else {
            $img = $img->fit($imgWidth,$imgHeight);
        }
        $img = $img->encode('jpg')->stream();
        if($img)
        {
            $ret = Storage::put($ImgPath,$img);
        }
        return $ret;
    }

    /**
     * 身分證隱私保護
     * @param string $bcid
     * @return string
     */
    public static function genBCID($bcid = '')
    {
        if(strlen($bcid) < 9) return $bcid;
        return substr($bcid,0,3).'*****'.substr($bcid,-2);
    }


    /**
     * 計算兩者日期的天數&月&年
     * @param int $add
     * @param string $startDate
     * @return false|string
     */
    public static function getBetweenDays($sdate , $edate = '', $type = '')
    {
        if(!$sdate) return 0;
        if(!$edate) $edate = date('Y-m-d');
        $firstDate  = new \DateTime($sdate);
        $secondDate = new \DateTime($edate);
        $intvl      = $firstDate->diff($secondDate);

        return (strtoupper($type) == 'Y')? $intvl->y : ((strtoupper($type) == 'M')? $intvl->m : $intvl->days);
    }

    /**
     * 計算？年前後的年
     * @param int $add
     * @param string $startDate
     * @return false|string
     */
    public static function addYear($add = 1, $startDate = '')
    {
        $startDate = ($startDate)? $startDate : date('Y-m-d');
        $addStr = ($add >= 0)? '+'.$add : $add;
        return date('Y-m-d', strtotime($addStr.' year', strtotime($startDate)));
    }

    /**
     * 計算？日前後的日期
     * @param int $add
     * @param string $startDate
     * @return false|string
     */
    public static function addDay($add = 1, $startDate = '')
    {
        $startDate = ($startDate)? $startDate : date('Y-m-d');
        $addStr = ($add >= 0)? '+'.$add : $add;
        return date('Y-m-d', strtotime($addStr.' day' , strtotime($startDate)));
    }

    /**
     * 字串去除多餘符號1
     * @param $str
     * @param int $isRemoveHtml
     * @return string|string[]
     */
    public static function tranStr($str)
    {
        $str = trim($str);
        if(!strlen($str)) return $str;

        $replaceAry1 = ["\r", "\n", "\r\n", "\n\r",'/','-',' ','＾','|','`','?','\'','"','\\',','];

        return str_replace($replaceAry1,'',$str);
    }

    /**
     * 字串去除多餘符號2
     * @param $str
     * @param int $isRemoveHtml
     * @return string|string[]
     */
    public static function tranStr2($str,$isRemoveHtml = 0)
    {
        $str = trim($str);
        if(!strlen($str)) return $str;

        $replaceAry1 = ['/','-',' ','＾','|','`','?','\'','"','\\',','];

        $ret = str_replace($replaceAry1,'',$str);
        if($isRemoveHtml)
        {
            $ret = strip_tags($ret);
        }

        return $ret;
    }

    /**
     * 轉換物件 成 陣列
     * @param $data
     * @return array
     */
    public static function toArray($data)
    {
        if(is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = SHCSLib::toArray($value);
            }
            return $result;
        }else{
            return $data;
        }
    }

    /**
     * 取得系統代碼
     * @param $code
     * @return array
     */
    public static function getCode($code,$isFirst = 0 ,$isApi = 0, $extAry = [])
    {
        $ret = array();
        if($isFirst){
            if($isApi)
            {
                $tmp = [];
                $tmp['id']   = '';
                $tmp['name'] = Lang::get('sys_base.base_10015');
                $ret[] = $tmp;
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }
        //1. 取得 系統代碼表
        $data = DB::table('sys_code')->select('status_key','status_val')->where('status_code',$code)->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('status_key',$extAry);
        }
        $data = $data->orderby('show_order')->get()->toArray();
        if(is_array($data) && count($data))
        {
            foreach ($data as $k => $v)
            {
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']   = $v->status_key;
                    $tmp['name'] = $v->status_val;

                    $ret[] = $tmp;
                } else {
                    $ret[$v->status_key] = $v->status_val;
                }
            }
        }

        return $ret;
    }

    /**
     * 接收 base64 編碼過的 圖片
     * @param $filepath
     * @param $imgStr
     * @return bool
     */
    public static function saveBase64ToImg($filepath,$filename,$imgStr,$reSizeAry = [])
    {
        $ret = '';
        if(!$filepath || !$filename || !$imgStr) return $ret;
        //檔名
        $filenameAry = explode('.',$filename);
        $filename_h = $filenameAry[0];
        //轉換
        $imageData = base64_decode($imgStr);
        //儲存圖片
        if(Storage::put($filepath.$filename, $imageData))
        {
            $ret = $filepath.$filename;
            //如果有指定轉換圖片大小
            if(count($reSizeAry))
            {
                foreach ($reSizeAry as $resize_val)
                {
                    //圖片大小
                    if(is_numeric($resize_val))
                    {
                        $newImgName = $filename_h.'_'.abs($resize_val).'.jpg';
                        //取得 轉換圖片後的內容
                        $img = \Image::make($imageData)->resize($resize_val,null, function ($constraint) {
                            $constraint->aspectRatio();
                        })->stream();
                        if($img)
                        {
                            Storage::put($filepath.$newImgName,$img);
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 回傳網址＋加密參數
     */
    public static function url($url,$id = '',$param = '')
    {
        $ret = $url;
        if(!$url) return $ret;

        if($id)
        {
            $ret .= SHCSLib::encode($id);
        }

        if(!is_array($param) && strlen($param)) //如果傳入字串
        {
            $ret .= '?'.$param;
        } elseif(is_array($param) && count($param)) { //如果是傳入陣列
            $paramStr = '';
            foreach ($param as $key => $val)
            {
                $k = $key;
                $v = $val;

                if($k && $v)
                {
                    if(!$paramStr)
                    {
                        $paramStr = '?'.$k.'='.$v;
                    } else {
                        $paramStr .= '&'.$k.'='.$v;
                    }
                }
            }
            if(strlen($paramStr)) $ret .= $paramStr;
        }

        return $ret;
    }

    /**
     * 加密函數 AES
     */
    public static function encode($data)
    {
        if(!$data) return $data;
        $shcs_key = config('mycfg.randkey','HttcSOzZG+HnS9zc');
        $shcs_iv  = config('mycfg.randiv','2018030112503034+HnS9zc');

        return base64_encode(openssl_encrypt($data,"AES-128-CBC",$shcs_key,0,$shcs_iv));
    }

    /**
     * 解密函數 AES
     */
    public static function decode($data)
    {
        if(!$data) return $data;
        $shcs_key = config('mycfg.randkey','HttcSOzZG+HnS9zc');
        $shcs_iv  = config('mycfg.randiv','2018030112503034');

        return openssl_decrypt(base64_decode($data),"AES-128-CBC",$shcs_key,0,$shcs_iv);
    }

}
