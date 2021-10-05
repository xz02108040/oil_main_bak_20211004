<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_door_supply_member extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商成員
     */
    protected $table = 'view_door_supply_member';
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
        $data = view_door_supply_member::where('b_supply_id',$sid)->where('b_cust_id',$uid)->
                select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    /**
     *  是否存在[身分證]
     * @param $id
     * @return int
     */
    protected function isBCIDExist($bc_id, $exID = 0)
    {
        if(!$bc_id) return 0;
        $data  = view_door_supply_member::where('bc_id',$bc_id);
        if($exID)
        {
            $data = $data->where('b_cust_id','!=',$exID);
        }
        $data = $data->select('b_cust_id')->first();

        return isset($data->b_cust_id)? $data->b_cust_id : 0;
    }


    protected function getSupplyID($uid)
    {
        if(!$uid) return 0;
        $data = view_door_supply_member::where('b_cust_id',$uid)->select('b_supply_id')->first();
        return isset($data->b_supply_id)? $data->b_supply_id : 0;
    }
    protected function getSupplyName($uid)
    {
        if(!$uid) return '';
        $data = view_door_supply_member::where('b_cust_id',$uid)->select('supply')->first();
        return isset($data->supply)? $data->supply : '';
    }

    protected function getProjectID($uid)
    {
        if(!$uid) return 0;
        $data = view_door_supply_member::where('b_cust_id',$uid)->select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    /**
     * 工程案件 CPC_TAG 人數
     * @param $uid
     * @return int
     */
    protected function getCPCTagAmt($project_id,$cpcTagAry = [])
    {
        if(!$project_id ) return 0;
        $data = view_door_supply_member::where('e_project_id',$project_id);
        if(count($cpcTagAry))
        {
            $data = $data->whereIn('cpc_tag',$cpcTagAry);
        }
        return $data->count();
    }

    protected function getData($uid)
    {
        if(!$uid) return 0;
        $data = view_door_supply_member::where('b_cust_id',$uid)->first();
        return $data;
    }

    protected function getCoursePassMember($supply_id)
    {
        if(!$supply_id) return [];
        $memberAry = view_door_supply_member::join('log_course_pass as l','l.b_cust_id','=','view_door_supply_member.b_cust_id')->
        where('l.sdate',date('Y-m-d'))->where('view_door_supply_member.b_supply_id',$supply_id);
        $memberAry = $memberAry->select('view_door_supply_member.b_cust_id','view_door_supply_member.name')->get()->toArray();

        return $memberAry;
    }

    /**
     * 取得 特定專案 成員，指定 <專案角色><工程身份>
     * @param $sid
     */
    protected function getProjectMemberSelect($project_id,$jobAry = [],$identity_id = 0,$isFirst = 1,$isApi = 1)
    {
        $ret = [];
        if(!$project_id) return $ret;
        $jobAry = (is_array($jobAry))? $jobAry : [$jobAry];
        $data = view_door_supply_member::where('e_project_id',$project_id);
        if(count($jobAry))
        {
            $data = $data->whereIn('job_kind',$jobAry);
        }
        $data = $data->select('b_cust_id','name')->orderby('job_kind')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
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
                        $tmp['id']   = $val->b_cust_id;
                        $tmp['name'] = $val->name;
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
     * 取得 特定承攬商 成員
     * @param $sid
     */
    protected function getSupplyMemberSelect($id)
    {
        $ret = [];
        if(!$id) return $ret;
        $data = view_door_supply_member::where('b_supply_id',$id)->select('b_cust_id','name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $ret[$val->b_cust_id] = $val->name;
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($supply_id , $isShow = 0, $isLogin = '', $isFisrt = 1)
    {
        $ret    = [];
        $data   = view_door_supply_member::where('b_supply_id',$supply_id)->
        select('b_cust_id as id','name','bc_id','project','project_no');
        if($isLogin && in_array($isLogin,['Y','N']))
        {
            $data = $data->where('isLogin',$isLogin);
        }
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
