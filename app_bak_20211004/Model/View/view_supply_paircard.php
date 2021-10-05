<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_license;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_supply_paircard extends Model
{
    /**
     * 使用者Table: 承攬商成員允許配卡白名單
     */
    protected $table = 'view_supply_paircard';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     *  欲配卡成員是否可配卡
     * @param $id
     * @return int
     */
    protected function isExist($uid)
    {
        if(!$uid) return 0;
        $data  = view_supply_paircard::where('view_supply_paircard.b_cust_id',$uid);

        return $data->count();
    }
    /**
     *  欲配卡成員是否已經被鎖定
     * @param $id
     * @return int
     */
    protected function isLock($uid)
    {
        if(!$uid) return 0;
        $data  = view_supply_paircard::join('log_paircard_lock as l','l.b_cust_id','=','view_supply_paircard.b_cust_id')->
                where('view_supply_paircard.b_cust_id',$uid)->where('l.isLock','Y');

        return $data->count();
    }

    /**
     *  工程案件
     * @param $id
     * @return int
     */
    protected function getProjectId($uid)
    {
        if(!$uid) return 0;
        $data  = view_supply_paircard::where('b_cust_id',$uid)->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    /**
     *  欲配卡成員是否已經被鎖定
     * @param $id
     * @return int
     */
    protected function getInfo($uid,$isApi = 0)
    {
        if(!$uid) return 0;
        $ret   = [];
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $data  = view_supply_paircard::where('b_cust_id',$uid);
        if($data->count())
        {
            if($isApi)
            {
                foreach ($data->get() as $val)
                {
                    $tmp =[];
                    $tmp['id']          = $val->b_cust_id;
                    $tmp['name']        = $val->name;
                    $tmp['supply']      = $val->supply;
                    $tmp['identity']    = \Lang::get('sys_api.identity3');
                    $tmp['identity_id'] = 3;
                    //工程身分
                    $identityAry = e_project_license::getUserIdentity($val->e_project_id,$val->b_cust_id,[$identity_A,$identity_B]);
                    if(count($identityAry))
                    {
                        foreach ($identityAry as $val2)
                        {
                            $tmp['identity']    = $val2['name'];
                            $tmp['identity_id'] = $val2['id'];
                        }
                    }
                    //是否有存在超過一年的圖片
                    $img_date   = date('Y-m-d',$val->head_img_at);
                    $today      = date('Y-m-d');
                    $img_years  = SHCSLib::getBetweenDays($img_date,$today,'Y');
                    $tmp['img'] = ($img_years >= 1)? '' : b_cust_a::getHeadImg($val->b_cust_id,2);
                    $ret[] = $tmp;
                }
            } else {
                $ret = $data->get();
            }
        }
        return $ret;
    }
    /**
     *  該承攬商是否有可配卡成員
     * @param $id
     * @return int
     */
    protected function isSupplyExist($supply_id,$isLock = 'Y')
    {
        if(!$supply_id) return 0;
        $data  = view_supply_paircard::where('b_supply_id',$supply_id);
        if($isLock == 'Y')
        {
            $data = $data->whereNotIn('b_cust_id',\DB::table('log_paircard_lock')->where('b_supply_id',$supply_id)->where('isLock','Y')->pluck('b_cust_id'));
        }

        return $data->count();
    }

    /**
     *  取得 可配卡之承攬商名單(P01010格式)
     * @param $id
     * @return int
     */
    protected function getSupplySelect($isApi = 0)
    {
        $ret  = [];
        $data =  view_supply_paircard::selectRaw('b_supply_id,supply,COUNT(b_supply_id) as amt')->groupby('b_supply_id','supply');

        if($isApi && $data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp =[];
                $tmp['id']      = $val->b_supply_id;
                $tmp['name']    = $val->supply;
                $ret[] = $tmp;
            }
        } else {
            $ret = $data->get();
        }

        return $ret;
    }

    /**
     *  取得 可配卡承攬商成員之名單(P01011格式)
     * @param $id
     * @return int
     */
    protected function getSupplyMemberSelect($supply_id , $isLock = 'Y', $isApi = 0)
    {
        $ret    = [];
        $utAry  = SHCSLib::getCode('UT_KIND',0);
        $jobAry = SHCSLib::getCode('JOB_KIND',0);
        $LockAry= \DB::table('log_paircard_lock')->where('isLock','Y')->pluck('b_cust_id')->toArray();

        $data =  view_supply_paircard::where('b_supply_id',$supply_id)->
        select('b_cust_id','name','isUT','project_no','project','job_kind','cpc_tag');
        if($isLock == 'Y')
        {
            $data = $data->whereNotIn('b_cust_id',$LockAry);
        }
        if($data->count())
        {
            if($isApi)
            {
                foreach ($data->get() as $val)
                {
                    $isUT = ($val->isUT == 'Y')? 'Y' : '';
                    $cpc  = '('.$val->cpc_tag.')';
                    $job  = (isset($jobAry[$val->job_kind])? $jobAry[$val->job_kind] : '');
                    $tmp = [];
                    $tmp['id']      = $val->b_cust_id;
                    $tmp['name']    = $val->name;
                    $tmp['project'] = $val->project_no.' '.$val->project;
                    $tmp['cpc_tag'] = $job.$cpc;
                    $tmp['isUT']    = $isUT;
                    $tmp['ut_name'] = isset($utAry[$isUT])? $utAry[$isUT] : '';
                    $tmp['isLock']  = in_array($val->b_cust_id,$LockAry)? 'Y' : 'N';
                    $ret[] = $tmp;
                }
                sort($ret);
            } else {
                $ret = $data->get();
            }
        }

        return $ret;
    }
}
