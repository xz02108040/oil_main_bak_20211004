<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_m;
use App\Model\Engineering\et_traning_time;
use App\Model\View\view_door_supply_member;
use Lang;
use DB;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * 教育訓練開課報名 維護
 *
 */
trait TraningMemberTrait
{
    public function createTraningMemberGroup($data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->member) && count($data->member)) return $ret;

        foreach ($data->member as $uid)
        {
            $UPD = [];
            $UPD['et_course_id']    = $data->et_course_id;
            $UPD['et_traning_id']   = $data->et_traning_id;
            $UPD['e_project_id']    = view_door_supply_member::getProjectID($uid);
            $UPD['b_supply_id']     = $data->b_supply_id;
            $UPD['isExcel']         = isset($data->isExcel)? $data->isExcel : 'N';
            $UPD['traning_date']    = isset($data->traning_date)? $data->traning_date : '';
            $UPD['traning_unit']    = isset($data->traning_unit)? $data->traning_unit : '';
            $UPD['b_cust_id']       = $uid;
            $UPD['apply_date']      = date('Y-m-d H:i:s');
            if(isset($data->aproc) && $data->aproc)
            {
                $UPD['aproc']       = $data->aproc;
            }
            if($this->createTraningMember($UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }
        return $suc;
    }


    /**
     * 新增 教育訓練開課報名
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createTraningMember($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->et_course_id)) return $ret;
        $now = date('Y-m-d H:i:s');
        $isPass = 0;

        $INS = new et_traning_m();
        $INS->et_course_id      = $data->et_course_id;
        $INS->et_traning_id     = $data->et_traning_id;
        $INS->e_project_id      = isset($data->e_project_id)? $data->e_project_id : 0;
        $INS->b_supply_id       = $data->b_supply_id;
        $INS->b_cust_id         = $data->b_cust_id;
        $INS->apply_date        = $data->apply_date ? $data->apply_date : $now;
        $INS->isExcel           = isset($data->isExcel) ? $data->isExcel : 'N';
        $INS->traning_unit      = isset($data->traning_unit) ? $data->traning_unit : '';
        if(isset($data->aproc) && $data->aproc)
        {
            $INS->aproc         = $data->aproc;
            $INS->charge_user   = $mod_user;
            $INS->charge_stamp  = $now;
            if($data->aproc == 'O') {
                $isPass = 1;
                $t_date             = (isset($data->traning_date))? $data->traning_date : '';
                $INS->pass_user     = $mod_user;
                $INS->pass_date     = $t_date ? $t_date.' 00:00:00' : $now;
                $v_day              = et_course::getValidday($data->et_course_id);//
                $INS->valid_date    = SHCSLib::addDay($v_day,$t_date);//有效日期
                //dd($data->et_course_id,$t_date,$v_day,SHCSLib::addDay($v_day,$t_date));
            }
        }
        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret && $isPass)
        {
            //2020-11-18 新增合格紀錄
            LogLib::putCoursePassLog($data->b_cust_id,$INS->valid_date);
        }
        return $ret;
    }

    /**
     * 審查教育訓練 通過
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool|int
     */
    public function setTraningMemberGroup($id,$data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->member) && count($data->member)) return $ret;

        foreach ($data->member as $rpid)
        {
            $UPD = [];
            $UPD['aproc'] = $data->aproc;
            if($this->setTraningMember($rpid,$UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }

        return $suc;
    }

    /**
     * 修改 教育訓練開課報名
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setTraningMember($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = $isPass = 0;

        $UPD = et_traning_m::find($id);
        if(!isset($UPD->id)) return $ret;
        //進度
        if(isset($data->aproc) && in_array($data->aproc,['A','B','P','R','O','C']) && $data->aproc !==  $UPD->aproc)
        {
            //[受理上課]A->P　監造同意
            if ($data->aproc == 'P' && $UPD->aproc == 'A') {
                //增加判斷，若已核可人數已滿，不可進行報名
                $IsEdit = et_traning_m::getTraningIsEdit($UPD->et_traning_id);
                if ($IsEdit) {
                    $isUp++;
                    $UPD->aproc = $data->aproc;
                    $UPD->charge_user2 = $mod_user;
                    $UPD->charge_stamp2 = $now;
                } else {
                    return  0;
                }
            }
            //[受理上課]P->R 工安課同意
            if ($data->aproc == 'R' && $UPD->aproc == 'P') {
                //增加判斷，若已核可人數已滿，不可進行報名
                $IsEdit = et_traning_m::getTraningIsEdit($UPD->et_traning_id);
                if ($IsEdit) {
                    $isUp++;
                    $UPD->aproc = $data->aproc;
                    $UPD->charge_user = $mod_user;
                    $UPD->charge_stamp = $now;
                } else {
                    return  0;
                }
            }

            //[不受理]A->R
            if($data->aproc == 'B')
            {
                $isUp++;
                $UPD->aproc = $data->aproc;
                if($UPD->aproc == 'A')
                {
                    $UPD->charge_user2  = $mod_user;
                    $UPD->charge_stamp2 = $now;
                } else {
                    $UPD->charge_user  = $mod_user;
                    $UPD->charge_stamp = $now;
                }
            }
            //[通過授課]P->O
            if($data->aproc == 'O' && $UPD->aproc == 'R')
            {
                $isUp++;
                $isPass++;
                $v_day           = et_course::getValidday($UPD->et_course_id);//有效天數
                list($et_course_id,$sdate) = et_traning::getCourseInfo($UPD->et_traning_id);
//                dd($UPD->et_traning_id,$v_day,$sdate);
                $UPD->aproc      = $data->aproc;
                $UPD->pass_user  = $mod_user;
                $UPD->pass_date  = $now;
                $UPD->valid_date = SHCSLib::addDay($v_day,$sdate);//有效日期
            }
            //[沒通過授課]P->C
            if($data->aproc == 'C' && $UPD->aproc == 'R')
            {
                $isUp++;
                $UPD->aproc = $data->aproc;
            }
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
            //2020-11-18 新增合格紀錄
            if($isPass) LogLib::putCoursePassLog($UPD->b_cust_id);
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 教育訓練開課報名 by 承攬商
     *
     * @return array
     */
    public function getApiTraningMemberMainList($tid,$course_id,$projectAry = [],$aproc = 'A')
    {
        $data = et_traning_m::join('b_supply as s','s.id','=','et_traning_m.b_supply_id')->
            selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
            groupby('b_supply_id');
        if($tid)
        {
            $data = $data->where('et_traning_m.et_traning_id',$tid);
        }
        if($course_id)
        {
            $data = $data->where('et_traning_m.et_course_id',$course_id);
        }
        if($projectAry)
        {
            $data = $data->whereIn('et_traning_m.e_project_id',$projectAry);
        }

        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 教育訓練開課報名 by 課程報名審查
     *
     * @return array
     */
    public function getApiTraningMemberList2($tid = 0, $cid = 0, $b_supply_id = 0, $b_cust_id = 0, $aproc = [], $isValid = '', $isHidden = '')
    {
        $ret = $ret1 = $ret2 = $ret3 = $ret4 = $ret5 = $ret6 = array();
        $courseAry = et_course::getSelect(0);
        $supplyAry = b_supply::getSelect(0);
        $aprocAry  = SHCSLib::getCode('COURSE_TRANING_APROC');
        $passAry   = SHCSLib::getCode('PASS');
        //取第一層
        $data = et_traning_m::join('b_cust as a','et_traning_m.b_cust_id','=','a.id')->
        join('b_cust_a as b','b.b_cust_id','=','et_traning_m.b_cust_id')->
        join('et_traning as t','t.id','=','et_traning_m.et_traning_id')->
        where('et_traning_m.isClose','N')->
        //whereRaw('DATEDIFF ( DAY, apply_date , getdate() ) <= 400')->
        select('et_traning_m.*','a.name','b.bc_id','t.et_traning_time_id','t.sdate as traning_date');
        if($tid)
        {
            $data = $data->where('et_traning_m.et_traning_id',$tid);
        }
        if($cid)
        {
            $data = $data->where('t.et_course_id',$cid);
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('et_traning_m.aproc',$aproc);
        }
        if($b_supply_id)
        {
            $data = $data->where('et_traning_m.b_supply_id',$b_supply_id);
        }
        if(is_array($b_cust_id) && count($b_cust_id))
        {
            $data = $data->whereIn('et_traning_m.b_cust_id',$b_cust_id);
        }elseif(is_numeric($b_cust_id) && $b_cust_id > 0)
        {
            $data = $data->where('et_traning_m.b_cust_id',$b_cust_id);
        }
        if($isValid == 'Y')
        {
            $data = $data->where('et_traning_m.valid_date','>=',date('Y-m-d'));
        }
        if($isValid == 'N')
        {
            $data = $data->where('et_traning_m.valid_date','<',date('Y-m-d'));
        }
        $data = $data->orderby('b_cust_id')->orderby('valid_date','desc')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = $v;
                if($isHidden == 'Y')
                {
                    $tmp['bc_id']   = SHCSLib::genBCID($v->bc_id);
                }
                $tmp['course']      = isset($courseAry[$v->et_course_id])? $courseAry[$v->et_course_id] : '';
                $tmp['supply']      = isset($supplyAry[$v->b_supply_id])? $supplyAry[$v->b_supply_id] : '';
                $tmp['aproc_name']  = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $tmp['week']        = et_traning_time::getName($v->et_traning_time_id);
                $tmp['pass_user']   = User::getName($v->pass_user);
                $tmp['charge_user'] = User::getName($v->charge_user);
                $tmp['close_user']  = User::getName($v->close_user);
                $tmp['new_user']    = User::getName($v->new_user);
                $tmp['mod_user']    = User::getName($v->mod_user);

                if($v->aproc == 'O' && !isset($ret1[$v->b_cust_id]))
                {
                    $ret1[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'R' && !isset($ret2[$v->b_cust_id]))
                {
                    $ret2[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'P' && !isset($ret3[$v->b_cust_id]))
                {
                    $ret3[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'A' && !isset($ret4[$v->b_cust_id]))
                {
                    $ret4[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'C' && !isset($ret5[$v->b_cust_id]))
                {
                    $ret5[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'B' && !isset($ret6[$v->b_cust_id]))
                {
                    $ret6[$v->b_cust_id] = (object)$tmp;
                }
            }
            if(count($ret1))
            {
                foreach ($ret1 as $uid => $val)
                {
                    $ret[$uid] = $val;
                }
            }
            if(count($ret2))
            {
                foreach ($ret2 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret3))
            {
                foreach ($ret3 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret4))
            {
                foreach ($ret4 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret5))
            {
                foreach ($ret5 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret6))
            {
                foreach ($ret6 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
        }

        return $ret;
    }

    /**
     * 取得 教育訓練開課報名 by 課程報名審查
     *
     * @return array
     */
    public function getApiTraningMemberList3($tid = 0, $cid = 0, $b_supply_id = 0, $b_cust_id = 0)
    {
        $ret = $ret1 = $ret2 = $ret3 = $ret4 = array();
        $courseAry = et_course::getSelect();
        $aprocAry  = SHCSLib::getCode('COURSE_TRANING_APROC');
        //取第一層
        $data = et_traning_m::join('b_cust as a','et_traning_m.b_cust_id','=','a.id')->
        join('b_cust_a as b','b.b_cust_id','=','et_traning_m.b_cust_id')->
        join('b_supply as s','s.id','=','et_traning_m.b_supply_id')->
        join('et_traning as t','t.id','=','et_traning_m.et_traning_id')->
        where('et_traning_m.isClose','N')->
        select('et_traning_m.e_project_id','et_traning_m.b_supply_id','et_traning_m.b_cust_id','a.name','b.bc_id',
            's.name as supply','s.tax_num','s.boss_name','s.tel1','s.fax1',
            's.address','t.et_traning_time_id','t.sdate as traning_date');
        $data = $data->whereIn('et_traning_m.aproc',['R','O']);
        if($tid)
        {
            $data = $data->where('et_traning_m.et_traning_id',$tid);
        }
        if($cid)
        {
            $data = $data->where('t.et_course_id',$cid);
        }
        if($b_supply_id)
        {
            $data = $data->where('et_traning_m.b_supply_id',$b_supply_id);
        }
        if(is_array($b_cust_id) && count($b_cust_id))
        {
            $data = $data->whereIn('et_traning_m.b_cust_id',$b_cust_id);
        }elseif(is_numeric($b_cust_id) && $b_cust_id > 0)
        {
            $data = $data->where('et_traning_m.b_cust_id',$b_cust_id);
        }
        $data = $data->orderby('b_supply_id')->orderby('e_project_id')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['project_no']      = e_project::getNo($v->e_project_id);
                $tmp['supply']          = $v->supply;
                $tmp['boss_name']       = $v->boss_name;
                $tmp['tax_num']         = $v->tax_num;
                $tmp['tel1']            = $v->tel1;
                $tmp['fax1']            = $v->fax1;
                $tmp['address']         = $v->address;
                $tmp['b_cust_id']       = $v->b_cust_id;
                $tmp['name']            = $v->name;
                $tmp['bc_id']           = $v->bc_id;
                $tmp['traning_date']    = $v->traning_date;
                $tmp['traning_time']    = et_traning_time::getTimeName($v->et_traning_time_id);

                $ret[$v->b_supply_id][$v->e_project_id][] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 取得 教育訓練開課報名
     *
     * @return array
     */
    public function getApiTraningMemberList($et_traning_id = 0, $et_course_id = 0, $b_supply_id = 0, $uid = 0, $aproc = [], $isValid = '',$isHidden = '')
    {
        $ret = $ret1 = $ret2 = $ret3 = $ret4 = $ret5 = $ret6 = array();
        $courseAry = et_course::getSelect();
        $aprocAry  = SHCSLib::getCode('COURSE_TRANING_APROC');
        $passAry   = SHCSLib::getCode('PASS');
        //取第一層
        $data = et_traning_m::join('view_user as a','et_traning_m.b_cust_id','=','a.b_cust_id')->
                join('et_traning as t','t.id','=','et_traning_m.et_traning_id')->
                where('et_traning_m.isClose','N')->
                select('et_traning_m.*','a.name','a.bc_id');
        if($et_traning_id)
        {
            $data = $data->where('et_traning_m.et_traning_id',$et_traning_id);
        }
        if($et_course_id)
        {
            $data = $data->where('t.et_course_id',$et_course_id);
        }
        //調整審核狀態只影響是否可勾選進行審核，不加入篩選條件
        // if(is_array($aproc) && count($aproc))
        // {
        //     $data = $data->whereIn('et_traning_m.aproc',['A']);
        // }
        if($b_supply_id)
        {
            $data = $data->where('et_traning_m.b_supply_id',$b_supply_id);
        }
        if($uid)
        {
            $data = $data->where('et_traning_m.b_cust_id',$uid);
        }
        if($isValid == 'Y')
        {
            $data = $data->where('et_traning_m.valid_date','>=',date('Y-m-d'));
        }
        if($isValid == 'N')
        {
            $data = $data->where('et_traning_m.valid_date','<',date('Y-m-d'));
        }
        $data = $data->orderby('et_traning_m.aproc')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = $v;

                $tmp['course']      = isset($courseAry[$v->et_course_id])? $courseAry[$v->et_course_id] : '';
                //如果通過，但是過期
                if($v->aproc == 'O' and strtotime($v->valid_date) < time())
                {
                    $tmp['aproc_name']  = $passAry[$v->aproc];
                    $tmp['isOver']      = 'Y';
                } else {
                    $tmp['aproc_name']  = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                    $tmp['isOver']      = 'N';
                }
                if($isHidden = 'Y')
                {
                    $tmp['bc_id']      = SHCSLib::genBCID($v->bc_id);
                }
                $tmp['supply']      = b_supply::getName($v->b_supply_id);
                $tmp['project']     = e_project::getName($v->e_project_id);
                $tmp['pass_user']   = User::getName($v->pass_user);
                $tmp['charge_user'] = User::getName($v->charge_user);
                $tmp['charge_user2']= User::getName($v->charge_user2);
                $tmp['close_user']  = User::getName($v->close_user);
                $tmp['new_user']    = User::getName($v->new_user);
                $tmp['mod_user']    = User::getName($v->mod_user);

                if($v->aproc == 'O' && !isset($ret1[$v->b_cust_id]))
                {
                    $ret1[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'R' && !isset($ret2[$v->b_cust_id]))
                {
                    $ret2[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'P' && !isset($ret3[$v->b_cust_id]))
                {
                    $ret3[$v->b_cust_id] = (object)$tmp;
                }
                elseif($v->aproc == 'A' && !isset($ret4[$v->b_cust_id]))
                {
                    $ret4[$v->b_cust_id] = (object)$tmp;
                }
            }
            if(count($ret1))
            {
                foreach ($ret1 as $uid => $val)
                {
                    $ret[$uid] = $val;
                }
            }
            if(count($ret2))
            {
                foreach ($ret2 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret3))
            {
                foreach ($ret3 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret4))
            {
                foreach ($ret4 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret5))
            {
                foreach ($ret5 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
            if(count($ret6))
            {
                foreach ($ret6 as $uid => $val)
                {
                    if(!isset($ret[$uid])) $ret[$uid] = $val;
                }
            }
        }

        return $ret;
    }
    /**
     * 取得 教育訓練開課報名 For APP [通過課程]
     *
     * @return array
     */
    public function getApiTraningMemberSelf($uid, $course = '', $aproc = ['O'])
    {
        $ret = array();
        $aprocAry  = SHCSLib::getCode('COURSE_TRANING_APROC');
        $overAry   = SHCSLib::getCode('TRANING_OVER');
        $importAry = SHCSLib::getCode('TRANING_IMPORT');
        //取第一層
        $data = et_traning_m::join('b_cust as a','et_traning_m.b_cust_id','=','a.id')->
                join('et_traning as t','t.id','=','et_traning_m.et_traning_id')->
                join('et_course as c','c.id','=','t.et_course_id')->
                select('et_traning_m.aproc','et_traning_m.pass_date','et_traning_m.valid_date','et_traning_m.isExcel',
            'et_traning_m.apply_date','et_traning_m.isClose','et_traning_m.b_supply_id',
            'a.name as user','c.name as course','t.course_no','t.et_traning_time_id',
            't.sdate as traning_sdate','t.sdate as traning_edate');
        $data = $data->where('et_traning_m.b_cust_id',$uid);
        if($course)
        {
            $data = $data->where('et_traning_m.et_course_id',$course);
        }
        if($aproc)
        {
            $data = $data->whereIn('et_traning_m.aproc',$aproc);
        }
        $data = $data->orderby('et_traning_m.isClose')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $val)
            {
                $tmp = [];
                $tmp['aproc_name']      = isset($aprocAry[$val->aproc])? $aprocAry[$val->aproc] : '';
                $tmp['week']            = et_traning_time::getName($val->et_traning_time_id);
                $tmp['supply']          = b_supply::getName($val->b_supply_id);
                $tmp['user']            = $val->user;
                $tmp['course']          = $val->course;
                $tmp['apply_date']      = substr($val->apply_date,0,19);
                $tmp['pass_date']       = $val->pass_date;
                $tmp['valid_date']      = $val->valid_date;
                $tmp['course_no']       = $val->course_no;
                $tmp['traning_sdate']   = $val->traning_sdate;
                $tmp['traning_edate']   = $val->traning_edate;
                $tmp['isClose']         = $val->isClose;
                $tmp['isExcel']         = isset($importAry[$val->isExcel])? $importAry[$val->isExcel] : '';
                $tmp['isOver']          = isset($overAry[$val->isClose])? $overAry[$val->isClose] : '';
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 檢查 教育訓練過期者，將其作廢
     *
     * @return array
     */
    public function getTranMemberApplyOrder($cource_id,$supply_id)
    {
        $ret = [];
        $data = et_traning_m::where('et_course_id',$cource_id)->where('b_supply_id',$supply_id)->where('isClose','N')->
            whereIn('aproc',['A','P','R'])->select('b_cust_id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[$val->b_cust_id] = $val->b_cust_id;
            }
            sort($ret);
        }
        return $ret;
    }

    /**
     * 檢查 教育訓練過期者，將其作廢
     *
     * @return array
     */
    public function checkTraningMemberOverDate()
    {
        $result = false;
        $uid = 1000000001;
        $now = date('Y-m-d H:i:s');

        //作廢
        $UPD = [];
        $UPD['isClose']     = 'Y';
        $UPD['close_user']  = $uid;
        $UPD['close_stamp'] = $now;
        $UPD['mod_user']    = $uid;
        $UPD['updated_at']  = $now;

        //找到已過期 教育訓練
        $ret = DB::table('et_traning_m')->where('isClose','N')->where('valid_date','<',date('Y-m-d'));

        //如果有，則作廢
        if($count = $ret->count())
        {
            $result = $ret->update($UPD);
        }

        return [$result,Lang::get('sys_base.base_10139',['name'=>$count])];
    }

}
