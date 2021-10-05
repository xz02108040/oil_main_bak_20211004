<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_list extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_list';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wid,$kind = 1)
    {
        if(!$wid) return 0;
        $data = wp_work_list::where('wp_work_id',$wid)->where('pmp_kind',$kind)->where('isClose','N')->first();
        return (isset($data->id))? $data->id : 0;
    }

    //比對　進度是否相符
    protected  function isAproc($id,$aproc)
    {
        if(!$id) return '';
        $data = wp_work_list::where('id',$id)->where('aproc',$aproc)->where('isClose','N');
        return $data->count();
    }
    //是否　
    protected  function isLock($id)
    {
        if(!$id) return [0,''];
        $data = wp_work_list::where('id',$id)->where('isLock','Y')->where('isClose','N')->select('lock_user','lock_stamp')->first();
        return (isset($data->lock_user))? [$data->lock_user,substr($data->lock_stamp,0,16)] : [0,''];
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_work_list::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 進度
    protected  function getAproc($id)
    {
        if(!$id) return '';
        $data = wp_work_list::find($id);
        return (isset($data->id))? $data->aproc : '';
    }

    //取得 申請資料
    protected  function getApply($id)
    {
        if(!$id) return ['',''];
        $data = wp_work_list::find($id);
        return (isset($data->id))? [User::getName($data->new_user),$data->apply_stamp] : ['',''];
    }


    //取得 完整資料
    protected  function getData($wid, $kind = 1)
    {
        if(!$wid) return '';
        $data = wp_work_list::where('wp_work_id',$wid)->where('isClose','N')->where('pmp_kind',$kind)->first();
        return $data;
    }
    //取得 最後更新時間
    protected  function getLastAt($wid, $kind = 1)
    {
        if(!$wid) return '';
        $data = wp_work_list::where('wp_work_id',$wid)->where('isClose','N')->where('pmp_kind',$kind)->select('id','updated_at')->first();

        return isset($data->id)? strtotime($data->updated_at) : 0;
    }

    //取得 名稱
    protected  function getProcessIDList($list_id,$kind = 1)
    {
        if(!$list_id) return [0,0];
        $data = wp_work_list::where('id',$list_id)->where('isClose','N')->where('pmp_kind',$kind)->first();

        return isset($data->id)? [$data->last_work_process_id,$data->wp_work_process_id] : [0,0];
    }

    /**
     * 針對「ReptPermit2Trait -> genPermitReptToday」取得工作許可證特定階段 數量
     * 2019-11-05 新增k3:主簽者階段
     * @return array
     */
    protected  function getReptPermitProcessAmt($store,$today = '',$dept = 0)
    {
        $ret = ['K1'=>0,'K2'=>0,'K3'=>0];
        if(!$today) $today = date('Y-m-d');
        $supply_process     = sys_param::getParam('PERMIT_PROCESS_SUPPLY'); //承攬商階段
        $root_process       = sys_param::getParam('PERMIT_PROCESS_EMP_ROOT');//轄區主簽者階段
        $supply_processAry  = explode(',',$supply_process);
        $root_processAry    = explode(',',$root_process);

        $data = wp_work_list::join('view_wp_work as w','wp_work_list.wp_work_id','=','w.id')->
            join('wp_work_process as p','wp_work_list.wp_work_process_id','=','p.id')->
            where('w.b_factory_id',$store)->where('w.sdate',$today)->where('w.isClose','N')->
            where('w.aproc','P')->selectRaw('p.wp_permit_process_id,COUNT(p.wp_permit_process_id) as amt')->
            where('wp_work_list.pmp_kind',1)->where('wp_work_list.isClose','N')->groupby('p.wp_permit_process_id');

        if (!empty($dept)) {
            if (is_array($dept)) {
                $data->whereIn('w.be_dept_id1', $dept);
            } else {
                $data->where('w.be_dept_id1', $dept);
            }
        }
    
        if($data->count())
        {
            $data = $data->get();
            //dd($data);
            foreach ($data as $val)
            {
                if(in_array($val->wp_permit_process_id,$supply_processAry))
                {
                    $ret['K1'] += $val->amt;
                }elseif(in_array($val->wp_permit_process_id,$root_processAry))
                {
                    $ret['K3'] += $val->amt;
                } else {
                    $ret['K2'] += $val->amt;
                }
            }
        }

        return $ret;
    }

    //取得 現在工作許可證的進度
    protected  function getNowProcessStatus($work_id, $kind = 1)
    {
        $aprocAry   = SHCSLib::getCode('PERMIT_APROC');
        $ret = [];
        $ret['list_aproc']          = $aprocAry['A'];
        $ret['now_process']         = Lang::get('sys_base.base_40227');
        $ret['process_target2']     = wp_permit_process_target::getTarget(1,2);
        $ret['process_charger2']    = '';
        $ret['process_stime2']      = '';
        $ret['process_etime2']      = '';
        $ret['last_process']        = '';
        $ret['process_target1']     = '';
        $ret['process_charger1']    = '';
        $ret['process_stime1']      = '';
        $ret['process_etime1']      = '';
        $ret['process_lock']        = '';
        $ret['last_charger']        = 0;
        $ret['last_work_process_id']= 0;
        $ret['now_work_process_id'] = 0;
        $ret['now_look_process_id'] = 0;
        $ret['now_process_allow_stop'] = 'N';
        $ret['work_stime'] = '';
        $ret['work_etime'] = '';
        if(!$work_id) return $ret;
        $data = wp_work_list::join('wp_work_process as wp','wp.id','=','wp_work_list.wp_work_process_id')->
                join('wp_permit_process as p','p.id','=','wp.wp_permit_process_id')->
                join('wp_work as w','w.id','=','wp_work_list.wp_work_id')->
                where('wp_work_list.wp_work_id',$work_id)->where('wp_work_list.isClose','N')->where('wp_work_list.pmp_kind',$kind)->
                select('p.*','wp_work_list.aproc','wp_work_list.wp_work_process_id','wp_work_list.work_stime','wp_work_list.work_etime',
            'wp_work_list.last_work_process_id','wp_work_list.isLock','wp_work_list.lock_user','wp_work_list.lock_stamp',
            'w.be_dept_id1','w.be_dept_id2','w.be_dept_id3','w.be_dept_id4','w.be_dept_id5')->first();
        if(isset($data->name))
        {
            $data->supply_worker    = wp_work_worker::getSelect($work_id,1,0,0);
            $data->supply_safer     = wp_work_worker::getSelect($work_id,2,0,0);
            $ret['list_aproc']          = isset($aprocAry[$data->aproc])? $aprocAry[$data->aproc] : '';
            $titleTarget            = wp_permit_process_title::getTitleTargetName($data->id);
            if($titleTarget) $titleTarget = '('.$titleTarget.')';
            $deptAry = [$data->be_dept_id1,$data->be_dept_id2,$data->be_dept_id3,$data->be_dept_id4,$data->be_dept_id5,$data->supply_worker,$data->supply_safer];
            //上次負責
            list($last_process_id,$last_title,$last_charger,$stime,$etime)  = wp_work_process::getProcessList($data->last_work_process_id);
            $ret['last_process']        = $last_title;
            $ret['last_process_id']     = $last_process_id;
            $ret['last_work_process_id']= $data->last_work_process_id;
            $ret['process_target1']     = wp_permit_process_target::getTarget($last_process_id,2,$deptAry);
            $ret['process_charger1']    = User::getName($last_charger);
            $ret['last_charger']        = $last_charger;
            $ret['process_stime1']      = !is_null($stime)? $stime : '';
            $ret['process_etime1']      = !is_null($etime)? $etime : '';
            //目前負責
            $ret['now_process']         = $data->title;
            $ret['now_process_id']      = $data->id;
            $ret['now_work_process_id'] = $data->wp_work_process_id;
            $ret['now_process_allow_stop'] = ($data->rule_reject_type > 0)? 'Y' : 'N';
            $ret['now_look_process_id'] = wp_work_process::isExist($work_id,$data->rule_look_process_id);
            $ret['process_target2']     = wp_permit_process_target::getTarget($data->id,2,$deptAry).$titleTarget;
            $ret['process_charger2']    = User::getName($data->charge_user);
            $ret['process_stime2']      = !is_null($data->stime)? $data->stime : '';
            $ret['process_etime2']      = !is_null($data->etime)? $data->etime : '';
            $ret['process_lock']        = ($data->isLock == 'Y')? \Lang::get('sys_api.E00200256',['param1'=>User::getName($data->lock_user),'param2'=>substr($data->lock_stamp,0,16)]) : '';
            //
            $ret['work_stime']      = !is_null($data->work_stime)? substr($data->work_stime,0,16) : '';
            $ret['work_etime']      = !is_null($data->work_etime)? substr($data->work_etime,0,16) : '';
        }

        return $ret;
    }

    //判斷 目前階段是否為 施工中，可提出收工申請
    protected  function isOnWorkProcess($id)
    {
        if(!$id) return '';
        $data               = wp_work_list::where('id',$id)->where('isClose','N')->first();
        //取得 目前工作程序ＩＤ
        $wp_work_process_id = (isset($data->id))? $data->wp_work_process_id : 0;
        $wp_work_id         = (isset($data->id))? $data->wp_work_id : 0;
        //取得 目前工作程序進行的階段
        $pid        = wp_work_process::getProcess($wp_work_process_id);
        //取得 下一個階段
        $new_pid    = wp_permit_process::nextProcess(1,1,$pid,'',$wp_work_id,$id,'Y');
        //取得兩者的 進度
        $oldaproc   = wp_permit_process::getAproc($pid);
        $newaproc   = wp_permit_process::getAproc($new_pid);

        //如果目前進度為 施工中 ＆ 下一個進度為施工作業
        return ($oldaproc == 'R' && $newaproc == 'O')? true : false;
    }
}
