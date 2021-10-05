<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_log_door_today;
use App\Model\View\view_user;
use App\Model\View\view_wp_work;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_worker extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_worker';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id,$uid,$iid = 0)
    {
        if(!$id) return 0;
        $data = wp_work_worker::where('wp_work_id',$id)->where('user_id',$uid)->where('isClose','N');
        if($iid)
        {
            $data = $data->where('engineering_identity_id',$iid);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //是否存在
    protected  function isLock($id,$uid)
    {
        if(!$id) return 0;
        $data = wp_work_worker::where('wp_work_id',$id)->where('user_id',$uid)->where('isLock','Y')->where('isClose','N')->
        select('id');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    //是否存在
    protected  function isReBackExist($work_id)
    {
        if(!$work_id) return 0;
        $err     = 0;
        $data = wp_work_worker::where('wp_work_id',$work_id)->where('isIN','Y')->where('isClose','N')->
        where('engineering_identity_id','!=',1)->select('user_id');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if(!rept_doorinout_t::isMenIn([$val->user_id], 0,'', [0,$work_id]))
                {
                    $err++;
                }
            }
        }
        return (!$err)? true : false;
    }
    //是否存在
    protected  function getUserAry($work_id)
    {
        $ret = [];
        if(!$work_id) return $ret;
        $data = wp_work_worker::where('wp_work_id',$work_id)->where('aproc','R')->where('isClose','N')->
        where('engineering_identity_id','!=',1)->select('user_id');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[] = $val->user_id;
            }
        }
        return $ret;
    }
    //是否存在
    protected  function getAry($id,$uid)
    {
        $ret = [];
        if(!$id) return [];
        $data = wp_work_worker::where('wp_work_id',$id)->where('user_id',$uid)->where('isClose','N')->select('id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[] = $val->id;
            }
        }
        return $ret;
    }
    //是否存在
    protected  function isApplyExist($work_id,$uid,$iid = 0,$aproc = 'A')
    {
        if(!$work_id || !$uid) return 0;
        $data = wp_work_worker::where('wp_work_id',$work_id)->where('user_id',$uid)->where('isClose','N');
        if($iid)
        {
            $data = $data->where('engineering_identity_id',$iid);
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('aproc',$aproc);
        }
        elseif(is_string($aproc))
        {
            $data = $data->where('aproc',$aproc);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //是否存在
    protected  function chkTranUserInfo($work_id,$uid)
    {
        if(!$work_id || !$uid) return 0;
        $data = wp_work_worker::where('wp_work_id',$work_id)->where('user_id',$uid)->where('isClose','N')->
                where('isIn','Y');
        $amt = $data->count();
        if($amt)
        {
            if($amt > 1)
            {
                return -1;
            } else {
                $data = $data->where('engineering_identity_id',9)->first();
                return isset($data->id)? $data->id : -2;
            }
        }
        return 0;
    }
    //是否存在
    protected  function getIdentity($id,$uid)
    {
        if(!$id) return 0;
        $data = wp_work_worker::where('wp_work_id',$id)->where('user_id',$uid)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->engineering_identity_id : 0;
    }
    //是否存在
    protected  function getAmt($id, $isLock = 'Y')
    {
        if(!$id) return 0;
        $data =  wp_work_worker::where('wp_work_id',$id)->where('isClose','N');
        if($isLock)
        {
            $data = $data->where('isLock',$isLock);
        }

        $data = $data->selectRaw('user_id')->groupBy('user_id')->get();
        return count($data);
    }
    //是否存在
    protected  function getRootMen($id, $identity_id = 0, $isLock = 0, $isApi = 0)
    {
        $ret = [];
        if(!$id) return $ret;
        $identityAry = ($identity_id)? [$identity_id] : [1,2];
        $data =  wp_work_worker::where('wp_work_id',$id)->whereIn('engineering_identity_id',$identityAry)->where('isClose','N');

        if($isLock)
        {
            $data = $data->where('isLock','Y')->select('user_id');
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']      = $val->user_id;
                    $tmp['name']    = view_user::getName($val->user_id);
                    $ret[] = $tmp;
                } else {
                    $ret[] = $val->user_id;
                }
            }
        }

        return $ret;
    }

    /*
     * ［承攬商］是否能簽核（工安＆工負＆施工人員須在廠）
     */
    protected  function hasWorkerDoorIn($work_id)
    {
        if(!$work_id) return 0;
        $doorInKindParam    = sys_param::getParam('PERMIT_SUPPLY_APROC_DOORIN_KIND',1);
        $rootIdentityParam1 = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $rootIdentityParam2 = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $rootIdentityAry    = [$rootIdentityParam1,$rootIdentityParam2];
        $ret = $doorINAmt1 = $doorINAmt2 = 0;
        $store_id           = wp_work::getStore($work_id);
        $project_id         = wp_work::getProjectId($work_id);
        $notReadyWorkAry    = view_wp_work::getNotReadyAry($project_id);
        $today              = date('Y-m-d');

        $data    = wp_work_worker::join('rept_doorinout_t as t','t.b_cust_id','=','wp_work_worker.user_id')->
                whereIn('wp_work_worker.wp_work_id',[0,$work_id])->where('wp_work_worker.isClose','N')->
                where('door_date',$today)->where('door_type',1)->whereIn('t.wp_work_id',$notReadyWorkAry)->
                where('b_factory_id',$store_id)->select('t.b_cust_id','engineering_identity_id','t.wp_work_id');
        //限定只管　工安＆工負皆在廠
        if($doorInKindParam == 1)
        {
            $data = $data->whereIn('engineering_identity_id',$rootIdentityAry);
        }
//        dd($project_id,$notReadyWorkAry,$data->get());

        if($data->count())
        {
            $worker1 = $worker2 = $worker3 = 0;
            foreach ($data->get() as $val)
            {
                if($val->engineering_identity_id == $rootIdentityParam1)
                {
                    $worker1++;
                }
                elseif($val->engineering_identity_id == $rootIdentityParam2)
                {
                    $worker2++;
                }
                elseif($doorInKindParam != 1)
                {
                    $worker3++;
                }
            }
            //dd($data->get(),$doorInKindParam,$worker1,$worker2,$worker3);

            if($doorInKindParam == 1)
            {
                //工安＆工負　皆在廠
                $ret = ($worker1 && $worker2)? 1 : 0;
            } else {
                //工安＆工負＆施工人員　皆在廠
                $ret = ($worker1 && $worker2 && $worker3)? 1 : 0;
            }
        }
        return $ret;
    }


    //取得 下拉選擇全部
    protected  function getSelect($wid, $iid = 0, $isFirst = 1, $isApi = 0, $aproc = '' , $extaIdentity = [], $isIn = '')
    {
        $ret    = $extUserAry = [];
        $today  = date('Y-m-d');
        $store  = wp_work::getStore($wid); //本工單所在廠區

        //欲排除特定工程身分之施工人員
        if(is_array($extaIdentity) && count($extaIdentity))
        {
            $data   = wp_work_worker::where('wp_work_id',$wid)->whereIn('engineering_identity_id',$extaIdentity)->select('user_id')->where('isClose','N');
            if($data->count())
            {
                foreach ($data->get() as $val)
                {
                    $extUserAry[] = $val->user_id;
                }
            }
        }

        //搜尋指定(特定工程身分)之施工人員
        $data   = wp_work_worker::where('wp_work_id',$wid)->select('id','user_id')->where('isClose','N');
        if($iid)
        {
            $data = $data->where('engineering_identity_id',$iid);
        }
        if(is_array($aproc) && count($aproc))
        {
            $data = $data->whereIn('aproc',$aproc);
        }
        elseif($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        $data = $data->get();
        //dd($data);
        if($isFirst){
            if($isApi)
            {
                $ret[]  = ['id'=>'','name'=>Lang::get('sys_base.base_10015'),'mobile'=>''];
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }

        foreach ($data as $key => $val)
        {
            //
            $isOk = 1;
            if($isIn)
            {
                //排除已經去做其他工作許可證的人
                $now_work_id = rept_doorinout_t::getWorkId($store,$today,$val->user_id);
                if($now_work_id != $wid && $now_work_id > 0)
                {
                    $isOk = 0;
                }
            }
            //排除特定工程身分之施工人員
            if(in_array($val->user_id,$extUserAry))
            {
                $isOk = 0;
            }

            if($isOk)
            {
                if($isApi == 1)
                {
                    list($user_name,$user_mobile)   = User::getMobileInfo($val->user_id);
                    $tmp = [];
                    $tmp['id']      = $val->user_id;
                    $tmp['name']    = $user_name;
                    $tmp['mobile']  = $user_mobile;
                    $ret[] = $tmp;
                } elseif($isApi == 2)
                {
                    $ret[$val->user_id] = view_user::getName($val->user_id);
                } else {
                    $ret[$val->user_id] = $val->user_id;
                }
            }
        }

        return $ret;
    }


    /**
     * 取得 工作許可證 - 人員 啟動後被鎖定的人
     * @param $wid
     * @param int $iid
     * @param int $isFirst
     * @param int $isApi
     * @return array
     */
    protected  function getLockMenSelect($wid, $iid = 0, $isFirst = 1, $isApi = 0, $isExtRoot = 0, $extAry = [], $isExtOtherOrder = 0)
    {
        $ret    = [];
        $today  = date('Y-m-d');
        $iidAry = b_supply_engineering_identity::getSelect(0);
        $root   = sys_param::getParam('IDENTITY_ROOT_ID');
        $rootAry= explode(',',$root);
        $store  = wp_work::getStore($wid);
        $data   = wp_work_worker::where('wp_work_id',$wid)->where('isLock','Y')->where('isClose','N');
        $data   = $data->select('id','user_id','engineering_identity_id');

        if(is_numeric($iid) && $iid > 0)
        {
            $data = $data->where('engineering_identity_id',$iid);
        } elseif(is_array($iid) && count($iid))
        {
            $data = $data->whereIn('engineering_identity_id',$iid);
        }
        if($isExtRoot)
        {
            $data = $data->whereNotIn('engineering_identity_id',$rootAry);
        }
        if(count($extAry))
        {
            $data = $data->whereNotIn('engineering_identity_id',$extAry);
        }
//        if($wid == 2964 && !$iid)dd($wid, $iid,$isExtRoot,$extAry,$data->get());
        $data = $data->get();
        if($isFirst){
            if($isApi)
            {
                $ret[]  = ['id'=>'','name'=>Lang::get('sys_base.base_10015')];
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }

        foreach ($data as $key => $val)
        {
            //
            $isOk = 1;
            if($isExtOtherOrder)
            {
                //排除已經去做其他工作許可證的人
                $now_work_id = rept_doorinout_t::getWorkId($store,$today,$val->user_id);
                if($now_work_id != $wid)
                {
                    $isOk = 0;
                }
            }

            if($isOk)
            {
                if($isApi)
                {
                    if(!isset($ret[$val->user_id]))
                    {
                        $tmp = [];
                        $tmp['id']          = $val->user_id;
                        $tmp['iid']         = $val->engineering_identity_id;
                        $tmp['name']        = view_user::getName($val->user_id);
                        $tmp['iid_name']    = isset($iidAry[$val->engineering_identity_id])? $iidAry[$val->engineering_identity_id] : '';
                        $ret[$val->user_id] = $tmp;
                    }
                } else {
                    $ret[$val->user_id] = $val->user_id;
                }
            }
        }
        if($isApi) sort($ret);

        return $ret;
    }

    /*
     * 確認該工程案件 以及該成員 是否有正在參與的工作許可證
     */
    protected  function hasInWork($project_id = 0, $b_cust_id = 0)
    {
        $ret  = 0;
        $type = 0;
        $aWhere = [];
        $aWhere2 = [];
        if(!$project_id && !$b_cust_id) return [$ret,$type];

        if($project_id){
            $aWhere[]  = array('wp_work.e_project_id', $project_id);
            $aWhere2[] = array('rept_doorinout_t.e_project_id', $project_id);
        }
        if($b_cust_id){
            $aWhere[]  = array('u.user_id', $b_cust_id);
            $aWhere2[] = array('rept_doorinout_t.b_cust_id', $b_cust_id);
        }

        $data = wp_work::join('wp_work_worker as u','u.wp_work_id','=','wp_work.id');
        $data = $data->where($aWhere);
        $data = $data->where('wp_work.isClose', 'N');
        $data = $data->where('u.isClose', 'N');
        $data = $data->whereNotIn('wp_work.aproc',['B','C','F']);
        $data = $data->where('wp_work.sdate','>',date('Y-m-d'));
        $data = $data->select('wp_work.id');
        $ret  = $data->count();
        if($ret)
        {
            $type = 1;
        } else {
            //檢查是否該成員仍然在工作許可證綁定中（門禁離場）
            $ret = rept_doorinout_t::where('wp_work_id','>',0)->where($aWhere2)->where('door_date',date('Y-m-d'))->count();
            if($ret)
            {
                $type = 2;
            }
        }


        return [$ret,$type];
    }

    /*
     * 取得　工單的工負／工安資訊
     */
    protected  function getWorkInfo($work_id, $showtype = 1)
    {
        $ret  = ['no'=>'','worker1'=>'','worker2'=>''];
        if(!$work_id) return $ret;
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);

        $data = wp_work::join('wp_work_worker as u','u.wp_work_id','=','wp_work.id');
        $data = $data->join('view_user as  v','u.user_id','=','v.b_cust_id');
        $data = $data->where('wp_work.id', $work_id)->where('wp_work.isClose', 'N');
        $data = $data->where('u.isClose', 'N');
        $data = $data->whereIn('u.engineering_identity_id', [$identity_A,$identity_B]);
        $data = $data->select('wp_work.id','wp_work.permit_no','v.b_cust_id','v.name','u.engineering_identity_id as iid');
        if($data->count())
        {

            foreach ($data->get() as $val)
            {
                if(!$ret['no']) $ret['no'] = $val->permit_no;

                $name = $val->name;
                if($showtype != 1)
                {
                    $UserStatus = rept_doorinout_t::getUserStatus($val->b_cust_id);
                    if($UserStatus) $name = $UserStatus;
                }

                if($val->iid == $identity_A && !$ret['worker1']) $ret['worker1'] = $name;
                if($val->iid == $identity_B && !$ret['worker2']) $ret['worker2'] = $name;
            }
        }

        return $ret;
    }

    /*
     * 確認該工程案件 以及該成員 是否有正在參與的工作許可證
     */
    protected  function checkWorkerNotInWork($work_id)
    {
        $ret  = [];
        if(!$work_id) return $ret;
        $today = date('Y-m-d');
        $data = wp_work_worker::where('wp_work_id', $work_id)->where('isClose', 'N');
        $data = $data->where('isIN','Y');
        $data = $data->select('user_id');
        if($data->count())
        {
            $userAry = [];
            foreach ($data->get() as $val)
            {
                $userAry[] = $val->user_id;
            }
            $isNotWorkMenAry = rept_doorinout_t::whereIn('b_cust_id',$userAry)->where('door_date',$today)->
            where('door_type',1)->where('wp_work_id',0)->select('name')->get();
            if(count($isNotWorkMenAry))
            {
                foreach ($isNotWorkMenAry as $val)
                {
                    $ret[] = $val->name;
                }
            }
        }

        return $ret;
    }
}
