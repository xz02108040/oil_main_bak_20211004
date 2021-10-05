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

class view_door_supply_whitelist extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商成員
     */
    protected $table = 'view_door_supply_whitelist';
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
        $data = view_door_supply_whitelist::where('b_supply_id',$sid)->where('b_cust_id',$uid)->
                select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    protected function getData($uid)
    {
        if(!$uid) return 0;
        $data = view_door_supply_whitelist::where('b_cust_id',$uid)->first();
        return $data;
    }

    /**
     * 取得 特定專案 成員，指定 <專案角色><工程身份>
     * @param $sid
     */
    protected function getProjectMemberWhitelistSelect($project_id,$cpcAry = [],$identity_id = 0,$isFirst = 1,$isApi = 0,$extAry = [])
    {
        $ret = [];
        if(!$project_id) return $ret;
        $cpcAry = (is_array($cpcAry))? $cpcAry : [$cpcAry];
        $data = view_door_supply_whitelist::where('e_project_id',$project_id);
        //2020-11-27 尿檢
        $data = $data->whereIn('isUT',['Y','C']);

        //
        if(count($cpcAry))
        {
            $data = $data->whereIn('cpc_tag',$cpcAry);
        }

        //1-3排除特定的人
        if(count($extAry))
        {
            $data = $data->whereNotIn('b_cust_id',$extAry);
        }
        $data = $data->select('b_cust_id','name')->orderby('job_kind')->get();
        if($isFirst){
            if($isApi)
            {
                $ret[] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
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

    //取得 下拉選擇全部
    protected  function getSelect($project_id , $isShow = 0, $isFisrt = 1)
    {
        $ret    = [];
        $data   = view_door_supply_whitelist::where('e_project_id',$project_id)->
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
