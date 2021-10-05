<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_door_supply_whitelist_pass extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商成員
     */
    protected $table = 'view_door_supply_whitelist_pass';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_cust_id';

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

    protected $guarded = ['b_cust_id'];

    protected function isExist($sid,$uid)
    {
        if(!$sid || !$uid) return 0;
        $data = view_door_supply_whitelist_pass::where('b_supply_id',$sid)->where('b_cust_id',$uid)->
                select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    protected function getData($uid)
    {
        if(!$uid) return 0;
        $data = view_door_supply_whitelist_pass::where('b_cust_id',$uid)->first();
        return $data;
    }

    /**
     * 取得 特定專案 成員，指定 <專案角色><工程身份>
     * @param $sid
     */
    protected function getProjectMemberWhitelistSelect($project_id,$cpcAry = [],$identity_id = 0,$isFirst = 1,$isApi = 0,$extAry = [],$work_sdate = '')
    {
        $ret = [];
        if(!$project_id) return $ret;
        $cpcAry = (is_array($cpcAry))? $cpcAry : [$cpcAry];
        $data = view_door_supply_whitelist_pass::where('view_door_supply_whitelist_pass.e_project_id',$project_id);
        //$data = $data->join('view_supply_etraning as e','e.b_cust_id','=','view_door_supply_whitelist_pass.b_cust_id');
        //2020-11-27 尿檢
        //$data = $data->whereIn('view_door_supply_whitelist_pass.isUT',['Y','C']);
        //2021-03-18 不包含 缺施工人員身分
        $data = $data->whereIn('view_door_supply_whitelist_pass.job_kind',[1,2,3,4]);

        //
        if(count($cpcAry))
        {
            $data = $data->whereIn('view_door_supply_whitelist_pass.cpc_tag',$cpcAry);
        }

        //1-3排除特定的人
        if(count($extAry))
        {
            $data = $data->whereNotIn('view_door_supply_whitelist_pass.b_cust_id',$extAry);
        }
        //1-4教育訓練過期
        if($work_sdate)
        {
            $data = $data->where('course_edate','>=',$work_sdate);
        }
        $data = $data->select('view_door_supply_whitelist_pass.b_cust_id','view_door_supply_whitelist_pass.name','course_edate');
        $data = $data->get();
        if($isFirst){
            if($isApi)
            {
                $ret[] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'course_edate'=>''];
            } else {
                $ret[0] = Lang::get('sys_base.base_10015');
            }
        }
        if(count($data))
        {
            foreach ($data as $val)
            {
                $isOk = 1;
                if($identity_id)
                {
                    if(!e_project_license::isExist($project_id,$val->b_cust_id,$identity_id))
                    {
                        $isOk = 0;
                    }
                }

                if($isOk){
                    if($isApi)
                    {
                        $tmp = [];
                        $tmp['id']           = $val->b_cust_id;
                        $tmp['name']         = $val->name;
                        $tmp['course_edate'] = $val->course_edate;
                        $ret[] = $tmp;
                    } else {
                        $ret[$val->b_cust_id] = $val->name;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 取得 特定專案 成員，指定 <工程身份><專案角色>
     * @param $sid
     */
    protected function getProjectMemberIdentitySelect($project_id,$cpcAry = [],$extIdentityAry = [],$extAry = [])
    {
        $ret = [];
        if(!$project_id) return $ret;
        $cpcAry = (is_array($cpcAry))? $cpcAry : [$cpcAry];
        //工程身分<名稱>陣列
        $identityNameAry      = b_supply_engineering_identity::getSelect(0);

        //1-1
        $data = view_door_supply_whitelist_pass::
                join('e_project_license as l','l.b_cust_id','=','view_door_supply_whitelist_pass.b_cust_id')->
                whereRaw('l.e_project_id = view_door_supply_whitelist_pass.e_project_id')->where('l.isClose','N')->
                where('view_door_supply_whitelist_pass.e_project_id',$project_id);
        //2020-11-27 尿檢
        // $data = $data->whereIn('isUT',['Y','C']);
        //2021-03-18 不包含 缺施工人員身分
        $data = $data->whereIn('job_kind',[1,2,3,4]);

        //1-2 中油標籤
        if(count($cpcAry))
        {
            $data = $data->whereIn('view_door_supply_whitelist_pass.cpc_tag',$cpcAry);
        }

        //1-3排除特定的人
        if(count($extIdentityAry))
        {
            $data = $data->whereNotIn('l.engineering_identity_id',$extIdentityAry);
        }

        //1-4排除特定的人
        if(count($extAry))
        {
            $data = $data->whereNotIn('view_door_supply_whitelist_pass.b_cust_id',$extAry);
        }
        $data = $data->select('view_door_supply_whitelist_pass.b_cust_id','view_door_supply_whitelist_pass.name','course_edate','l.engineering_identity_id');
        // $data = $data->groupby('view_door_supply_whitelist_pass.b_cust_id','view_door_supply_whitelist_pass.name','course_edate','l.engineering_identity_id');
        $results = $data->get();
        $check_exists = [];
        if(count($results))
        {
            $memberAry = [];
            foreach ($results as $val)
            {
                if (!isset($check_exists[$val->engineering_identity_id])) {
                    $check_exists[$val->engineering_identity_id] = [];
                }
                if (!in_array($val->b_cust_id, $check_exists[$val->engineering_identity_id])) {
                    $check_exists[$val->engineering_identity_id][] = $val->b_cust_id;
                    $memberAry[$val->engineering_identity_id][] = ['id'=>$val->b_cust_id,'name'=>$val->name,'course_edate'=>$val->course_edate];
                }
            }
            foreach ($memberAry as $identity_id => $val)
            {
                $tmp = [];
                $tmp['id']      = $identity_id;
                $tmp['name']    = isset($identityNameAry[$identity_id])? $identityNameAry[$identity_id] : '';
                $tmp['member']  = $val;
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id , $isShow = 0, $isFisrt = 1)
    {
        $ret    = [];
        $data   = view_door_supply_whitelist_pass::where('e_project_id',$project_id)->
        select('b_cust_id as id','name','bc_id','project','project_no');
        $data   = $data->get();
        if($isFisrt) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $showMemo = '';
            if($isShow)         $showMemo .= SHCSLib::genBCID($val->bc_id);
            if($isShow == 2)    $showMemo .= '，'.$val->project_no.' '.$val->project;
            $ret[$val->id] = $val->name. ($isShow? '('.$showMemo.')' : '');
        }

        return $ret;
    }
}
