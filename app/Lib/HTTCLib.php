<?php
namespace App\Lib;

use App\Model\Bcust\b_cust_a;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_process_title;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_img;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;
use DB;
use Storage;
use Image;
use Lang;
use File;
use Session;

class HTTCLib {

    /**
     * [工作許可證使用] 產生此對象是否有資格執行，以及所擁有身份陣列
     * @param $b_cust_id
     * @param $bc_type
     * @return array
     */
    public static function isTargetList($work_id,$b_cust_id,$process_id = 0)
    {
        $isTarget         = 1;
        $isOp             = $be_title = 0;
        $myTarget         = [0];
        $myAppType        = '';
        $bc_type          = User::getBcType($b_cust_id);

        $wp_work          = wp_work::getData($work_id);
        $supply_worker    = wp_work_worker::getSelect($work_id,1,0,0);
        $supply_safer     = wp_work_worker::getSelect($work_id,2,0,0);
        $workerAry        = array_merge($supply_worker,$supply_safer);
        $dept1            = $wp_work->be_dept_id1;
        $dept2            = $wp_work->be_dept_id2;
        $dept3            = $wp_work->be_dept_id3;
        $dept4            = $wp_work->be_dept_id4;
        $dept5            = $wp_work->be_dept_id5;
        $store            = $wp_work->b_factory_id;

        $listData         = wp_work_list::getData($work_id);
        //如果沒有指定階段，則找出目前的階段
        if(!$process_id)
        {
            $work_process_id  = isset($listData->wp_work_process_id)? $listData->wp_work_process_id : 0;
            $process_id       = ($work_process_id)? wp_work_process::getProcess($work_process_id) : 1;
        }

        if($bc_type == 2)
        {
            list($myDept,$be_title)   = b_cust_e::getEmpInfo($b_cust_id);//部門，簽核腳色
            //是否為簽核角色
            $isTitleTarget    = wp_permit_process_title::isTitleTarget($process_id,$be_title);//對象
            if($isTitleTarget)
            {
                //轄區主簽者 (需判斷下一層部門)
                if($be_title == 3)
                {
                    $myDeptAry = be_dept::getLevelDeptAry($myDept);
                    $isTarget  = (in_array($dept1,$myDeptAry))? 1 : 0;
                    if(!$isTarget && in_array($myDept,[$dept2,$dept3,$dept4])) $isTarget = 1;
                }
                //廠區主簽者 (需判斷同廠)
                if($be_title == 4)
                {
                    $myStore    = view_dept_member::getStore($b_cust_id);
                    $isTarget   = ($myStore == $store)? 1 : 0;
                }
            } else {
                $isTarget = 0;
            }
//            dd($process_id,$be_title,$isTitleTarget,$isTarget);
        }

        if($isTarget)
        {
            if($be_title == 4 && in_array($process_id, [1,4,5,6,9,10,12,13,14,15,17,18,21,23,25])) // 若為工廠長可以簽所有轄區的簽核階段
            {
                //1. 工廠長　
                $isOp       = 1;
                $myTarget   = ($process_id == 7)? [1,9] : [9];
                $myAppType  = 'FACTORY_BOSS';
            }elseif($be_title == 3 && $myDept == $dept1 && $process_id == 13)
            {
                //1. 轄區主簽者　
                $isOp       = 1;
                $myTarget   = [1,9];
                $myAppType  = 'FACTORY_ADMIN';
            } else {
                //2. 簽核單位
                $target = wp_permit_process_target::getTarget($process_id,1,[$dept1,$dept2,$dept3,$dept4,$dept5,$supply_worker,$supply_safer]);//對象

                list($isOp,$myTarget,$myAppType) =  HTTCLib::genPermitTarget($b_cust_id,$bc_type,$workerAry,$target,$supply_worker,$supply_safer,$dept1,$dept2,$dept3,$dept4,$dept5);

//                if($process_id == 13)dd($myDept,$be_title,$myDeptAry,$isTitleTarget,$target,$isTarget,$isOp,$myTarget,$myAppType);
                //        dd(['isOp'=>$isOp,'myTarget'=>$myTarget,'myAppType'=>$myAppType,'work_id'=>$work_id,
//        'process_id'=>$process_id,'target'=>$target,'supply_worker'=>$supply_worker,
//         'supply_safer'=>$supply_safer,'dept1'=>$dept1,'dept2'=>$dept2,'dept3'=>$dept3,'dept4'=>$dept4,'dept5'=>$dept5]);

            }

        }

        return array($isOp,$myTarget,$myAppType);
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
    public static function genPermitTarget($b_cust_id,$bc_type,$workerAry,$target,$supply_worker,$supply_safer,$dept1,$dept2,$dept3,$dept4,$dept5 = 0)
    {
        $isOp       = 0;
        $myTarget   = [0];
        $myAppType  = '';
        //dd($b_cust_id,$bc_type,$workerAry,$target,$supply_worker,$supply_safer,$dept1,$dept2,$dept3,$dept4,$dept5);
        //承攬商
        if($bc_type == 3) {
            //身份：工負＆工安
            $isSupply = in_array($b_cust_id,$workerAry) ? 1 : 0;
            if($isSupply)
            {
                $myAppType = 'SUPPLY_NO_ALLOW';
            }
            //權限：該工作許可證指定之工負＆工安
            if(in_array($target,[3,6]) && $isSupply)
            {
                $isOp   = 1;
                $tmp    = [3];
                if($supply_worker == $b_cust_id) $tmp[] = 4;
                if($supply_safer == $b_cust_id) $tmp[] = 5;
                $myTarget  = $tmp;
                $myAppType = 'SUPPLY_ANY';
            }
            //權限：該工作許可證指定之工負
            if($target == 4 && in_array($b_cust_id,$supply_worker))
            {
                $isOp   = 1;
                $myTarget  = [$target,3];
                $myAppType = 'SUPPLY_ROOT';
            }
            //權限：該工作許可證指定之工安
            if($target == 5 && in_array($b_cust_id,$supply_safer))
            {
                $isOp   = 1;
                $myTarget  = [$target,3];
                $myAppType = 'SUPPLY_SAFER';
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
//            dd($dept,$target,$dept1,$dept2,$dept3,$dept4,$dept5);

            if($dept)
            {
                $myAppType = 'DEPT_NO_ALLOW';
            }
            //權限：監造部門
            if($target == 1 && $isDept2)
            {
                $isOp   = 1;
                $myTarget  = [$target,9];
                $myAppType = 'DEPT_SUPER';
            }
            //權限：轄區部門
            elseif($target == 2 && $isDept1)
            {
                $isOp      = 1;
                $myTarget  = [$target,9];
                $myAppType = 'DEPT_LOCAL';
            }
            //權限：轄區部門上層部門
            elseif($target == 10 && $isDept5)
            {
                $isOp      = 1;
                $myTarget  = [$target,9];
                $myAppType = 'DEPT_LOCAL_ADMIN';
            }
            //權限：監工部門
            elseif($target == 7 && $isDept3)
            {
                $isOp      = 1;
                $myTarget  = [$target,9];
                $myAppType = 'DEPT_LOOK';
            }
            //權限：會簽部門
            elseif($target == 8 && $isDept4)
            {
                $isOp      = 1;
                $myTarget  = [$target,9];
                $myAppType = 'DEPT_OTHER';
            }
            //權限：所有部門
            elseif(in_array($target,[9,6]) && $dept)
            {
                $isOp      = 1;
                $tmp = [9];
                if($isDept1) $tmp[] = 1;
                if($isDept2) $tmp[] = 2;
                if($isDept3) $tmp[] = 7;
                if($isDept4) $tmp[] = 8;
                $myTarget  = $tmp;
                $myAppType = 'DEPT_ANY';
            }
        }
        return array($isOp,$myTarget,$myAppType);
    }
    /**
     * 取得工作許可證對象姓名
     * @param $app
     * @param int $retType
     * @return int|string
     */
    public static function genPermitTargetName($app,$retType = 1,$workList = [0,0,0,0,0,[],[]])
    {
        $ret = '';
        list($dept1,$dept2,$dept3,$dept4,$dept5,$rooter,$safer) = $workList;
        $dept1 = be_dept::getName($dept1);
        $dept1N= ($dept1)? '('.$dept1.')' : '';
        $dept2 = be_dept::getName($dept2);
        $dept2N= ($dept2)? '('.$dept2.')' : '';
        $dept3 = be_dept::getName($dept3);
        $dept3N= ($dept3)? '('.$dept3.')' : '';
        $dept4 = be_dept::getName($dept4);
        $dept4N= ($dept4)? '('.$dept4.')' : '';
        $dept5 = be_dept::getName($dept5);
        $dept5N= ($dept5)? '('.$dept5.')' : '';
        $rooter= User::getName($rooter);
        $rootN = ($rooter)? '('.$rooter.')' : '';
        $safer = User::getName($safer);
        $saferN= ($safer)? '('.$safer.')' : '';
        $allN= ($safer)? '('.$rooter .'，'.$safer.')' : '';

        if(in_array(1,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40228').$dept2N : 1; //監造部門
        }
        elseif (in_array(2,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40229').$dept1N : 2; //轄區部門
        }
        elseif (in_array(3,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40230').$allN : 3; //工地負責人＆安衛人員 皆可以
        }
        elseif (in_array(4,$app) && in_array(5,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40230').$allN : 3; //工地負責人＆安衛人員 皆可以
        }
        elseif (in_array(4,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40231').$rootN : 4; //安衛人員
        }
        elseif (in_array(5,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40232').$saferN : 5; //工地負責人
        }
        elseif (in_array(6,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40233') : 6; //任一部門<包含承攬商>
        }
        elseif (in_array(7,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40234').$dept3N : 7; //監工部門
        }
        elseif (in_array(8,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40235').$dept4N : 8; //會簽部門
        }
        elseif (in_array(9,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40238') : 9; //所有部門
        }
        elseif (in_array(10,$app))
        {
            $ret = ($retType == 2)? Lang::get('sys_base.base_40246').$dept5N : 10; //轄區部門上一級部門
        }
        return $ret;
    }
    /**
     * 顯示工作許可證 在廠狀態
     * @param $store_id
     * @param $b_cust_id
     * @return array
     */
    public static function getMenDoorStatus($store_id,$b_cust_id)
    {
        //尚未在廠
        $status = Lang::get('sys_base.base_40239');
        $date   = date('Y-m-d');
        //是否已經在廠
        if($isIn = rept_doorinout_t::isExist($store_id,0,$date,[$b_cust_id],1,[],NULL))
        {
            $inData     = rept_doorinout_t::find($isIn);
            $door_stamp = substr($inData->door_stamp,0,16);
            $status     = $inData->wp_work_id ? 'base_40242' : 'base_40241' ; //執行工作許可證中 : 尚未執行工作許可證

            $work_id    = ($inData->wp_work_id)? $inData->wp_work_id : 0;//LogLib::getLogDoorWorkID($inData->log_door_inout_id);

            $work_no    = $work_id ? wp_work::getNo($work_id) : '';
            if($status == 'base_40241' && $work_no) $work_no = '('.$work_no.')';
            $memo       = empty($work_no) ? '' : Lang::get('sys_base.'.$status,['memo'=>$work_no]);
            //已在廠，在廠時間：:time，:status
            $status     = Lang::get('sys_base.base_40240',['time'=>$door_stamp,'status'=> $memo]);
        }
        return [$isIn,$status];
    }

    /**
     * 是否在本次驗車日內驗車
     * @param $inspection_date 本次驗車日
     * @param $sdate 上次預計驗車日
     * @return string|string[]
     */
    public static function isInspection($inspection_date,$last_inspection_date,$sdate)
    {
        if(!CheckLib::isDate($last_inspection_date) || !CheckLib::isDate($inspection_date) ) return 'N';
        $today   = date('Y-m-d');
        $car_years = SHCSLib::getBetweenDays($sdate,$today,'Y');
        if($car_years <= 5)
        {
            return (strtotime($last_inspection_date) >= strtotime($inspection_date))? 'Y' : 'N';
        } else {
            return (strtotime($last_inspection_date) <= strtotime($inspection_date))? 'Y' : 'N';
        }
    }
    /**
     * 產生下次驗車日
     * @param $sdate 發證日
     * @return string|string[]
     */
    public static function genNextInspectionDate($sdate,$last_date = '')
    {
        $addMonth = $car_years = $dff_months = 0;
        $today   = date('Y-m-d');
        if(!CheckLib::isDate($sdate) ) return $sdate;
        if(!CheckLib::isDate($last_date)) $last_date = date('Y').substr($sdate,4);

        //1-1預測下次驗車日
        $nextYear       = $last_date;
        $isOver         = (strtotime($nextYear) <= strtotime($today))? false : true;
        $year_months    = SHCSLib::getBetweenDays($nextYear,$today,'M');

        //2-1.車齡
        $car_years = SHCSLib::getBetweenDays($sdate,$today,'Y');
        //2-2.驗車週期
        if($car_years < 5)
        {
            $addMonth = 12 * 5;
        }elseif($car_years >= 5 && $car_years < 10)
        {
            $addMonth = 12 * 1;
        }else
        {
            $addMonth = 12 * 1;//6;
        }

        if($isOver)
        {
            $dff_months = $year_months - $addMonth;
            if($addMonth == 6 && $dff_months > 0)
            {
                $nextYear = date('Y-m-d', strtotime("-".($addMonth)." months", strtotime($last_date)));
            }
        } else {
            $nextYear = date('Y-m-d', strtotime("+$addMonth months", strtotime($last_date)));
        }


//        return [$sdate,$last_date,$car_years,$isOver,$dff_months,$addMonth,$nextYear];
        return $nextYear;
    }
}