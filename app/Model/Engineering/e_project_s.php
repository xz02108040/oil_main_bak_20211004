<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\WorkPermit\wp_permit_identity;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_s extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_s';
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
     *  是否存在[使用者]
     * @param $id
     * @return int
     */
    protected function isExist($e_project_id,$uid, $kind = 0)
    {
        if(!$e_project_id || !$uid) return 0;
        $data  = e_project_s::where('e_project_id',$e_project_id)->where('b_cust_id',$uid)->where('isClose','N');
        if($kind)
        {
            $data = $data->where('job_kind',$kind);
        }
        return $data->count();
    }

    /**
     *  該人員 是否為工安人員＆工負人員
     * @param $id
     * @return int
     */
    protected function isAdExist($e_project_id)
    {
        if(!$e_project_id) return false;
        //1. 找到該案件 是否有「工地負責人」＆「工安人員」的工程身份
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $worker1    = view_door_supply_member::getProjectMemberSelect($e_project_id,[1,2],$identity_A,0,0);
        $worker2    = view_door_supply_member::getProjectMemberSelect($e_project_id,[1,3],$identity_B,0,0);
        //dd($worker1,$worker2);
        //2. 先針對 工地負責人，如果只有一人的工地負責人
        $worker_amt1= count($worker1);
        if($worker_amt1 == 1)
        {
            foreach ($worker1 as $uid => $user_name)
            {
                if(isset($worker2[$uid])) unset($worker2[$uid]);
            }
        }
        //3. 再針對 工安人員
        $worker_amt2 = count($worker2);

        return ($worker_amt1 && $worker_amt2)? true : false;
    }

    /**
     *  是否存在[工作身份]
     * @param $id
     * @return int
     */
    protected function isAdUser($id,$uid)
    {
        if(!$id && !$uid) return 0;
        $jobkindAry = explode(',',sys_param::getParam('DOOR_JOB_KIND','2,3'));

        $data  = e_project_s::where('e_project_id',$id)->where('b_cust_id',$uid)->whereIn('job_kind',$jobkindAry)->where('isClose','N');
        return $data->count();
    }

    /**
     *  是否存在[工作身份]
     * @param $id
     * @return int
     */
    protected function getUTName($id,$uid,$type = 1)
    {
        if(!$id && !$uid) return '';
        $utAry = SHCSLib::getCode('UT_KIND');

        $data  = e_project_s::where('e_project_id',$id)->where('b_cust_id',$uid)->where('isClose','N')->select('isUT')->first();

        if($type == 2)
        {
            return (isset($data->isUT) && $data->isUT == 'Y' && isset($utAry[$data->isUT]))? $utAry[$data->isUT] : '';
        } else {
            return (isset($data->isUT) && isset($utAry[$data->isUT]))? $utAry[$data->isUT] : '';
        }
    }

    /**
     *  是否存在[工作身份]
     * @param $id
     * @return int
     */
    protected function getLicense($id,$uid)
    {
        if(!$id && !$uid) return 0;
        $jobkindAry = explode(',',sys_param::getParam('DOOR_JOB_KIND','2,3'));

        $data  = e_project_s::where('e_project_id',$id)->where('b_cust_id',$uid)->whereIn('job_kind',$jobkindAry)->where('isClose','N');
        return $data->count();
    }

    //取得 工作身分相關的人
    protected  function getJobUser($id,$cpc_tag = 'C',$isApi = 0)
    {
        $ret = [];
        if(!$id) return $ret;
        $data = e_project_s::
                join('view_user as u','u.b_cust_id','=','e_project_s.b_cust_id')->
                where('e_project_s.e_project_id',$id)->where('e_project_s.cpc_tag',$cpc_tag)->where('e_project_s.isClose','N')->
                select('u.b_cust_id','u.name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']   = $val->b_cust_id;
                    $tmp['name'] = $val->name;
                    $ret[] = $tmp;
                } else {
                    $ret[] = $val->b_cust_id;
                }
            }
        }

        return $ret;
    }

    //取得 工作身分
    protected  function getJobList($id,$uid)
    {
        $ret = '';
        $job_kind = 0;
        if(!$id || !$uid) return [$job_kind,$ret];
        $jobAry = SHCSLib::getCode('JOB_KIND');

        $data = e_project_s::where('e_project_id',$id)->where('b_cust_id',$uid)->where('isClose','N')->
        select('job_kind','cpc_tag')->first();
        if(isset($data->job_kind))
        {
            $job_kind = $data->job_kind;
            $cpc_tag  = $data->cpc_tag;
            if($job_kind != 4 && $cpc_tag == 'A') $job_kind = 2;
            if($job_kind != 4 && $cpc_tag == 'B') $job_kind = 3;
            if($job_kind != 4 && $cpc_tag == 'E') $job_kind = 4;
            $ret = isset($jobAry[$job_kind])? $jobAry[$job_kind] : '';
        }

        return [$job_kind,$ret];
    }

     //取得 工作身分 與supply相同
     protected  function getJobListSupply($e_project_id,$uid)
     {
         $ret = [1,''];
         if(!$uid) return $ret;
 
         $data = e_project_s::where('b_cust_id',$uid)->where('isClose','N');
         if($e_project_id)
         {
             $data = $data->where('e_project_id',$e_project_id);
         }
         $data = $data->select('job_kind','cpc_tag')->first();
 
         return isset($data->job_kind)? [$data->job_kind,$data->cpc_tag] : $ret;
     }

    /**
     * 取得角色代表的顏色
     * @return array
     */
    protected function getJobKindColorSet()
    {
        return [1=>6,2=>2,3=>4,4=>7];
    }
    /**
     * 取得工作身份代表的顏色
     * @return array
     */
    protected function getIdentityColorSet()
    {
        return [1=>2,2=>4,3=>3,9=>6,4=>7];
    }

    /**
     * 找到工程案件之成員 是否有指定的工程身份
     * @param $id
     * @param int $identity_id
     * @return string
     */
    protected  function getIdentityMemberList($id,$identity_id = 1)
    {
        $member  = [];
        $kindColor = e_project_s::getIdentityColorSet();
        if(!$id) return '';
        $color = (isset($kindColor[$identity_id]))? $kindColor[$identity_id] : 6;

        $data = e_project_s::join('e_project_license as e','e.b_cust_id','=','e_project_s.b_cust_id')->
                join('view_user as u','u.b_cust_id','=','e_project_s.b_cust_id')->
                whereRaw('e.e_project_id = e_project_s.e_project_id')->
                where('e_project_s.e_project_id',$id)->where('e_project_s.isClose','N')->
                where('e.isClose','N')->where('e.engineering_identity_id',$identity_id)->
                select('u.b_cust_id','u.name');
        $data = $data->get();

        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $member[$val->b_cust_id] = HtmlLib::btn('#',$val->name,$color);
            }
        }

        return implode('，',$member);
    }

    //取得 下拉選擇全部
    protected  function getSelect($id,$job_kind = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = e_project_s::where('e_project_id',$id)->select('b_cust_id')->where('isClose','N');
        if($job_kind)
        {
            $data = $data->where('job_kind',$job_kind);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->b_cust_id] = User::getName($val->b_cust_id);
        }

        return $ret;
    }



}
