<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_supply_user extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商成員
     */
    protected $table = 'view_supply_user';
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

    protected function isExist($name)
    {
        if(!$name) return [];
        $data = view_supply_user::where(function ($query) use ($name) {
                    $query->where('name', 'like', '%'.$name.'%');
                    $query->orwhere('bc_id', $name);
                })->first();
        return $data;
    }
    protected function isSupply($b_cust_id)
    {
        if(!$b_cust_id) return 0;
        $data  = view_supply_user::where('b_cust_id',$b_cust_id);
        return $data->count();
    }

    /**
     *  是否存在[身分證]
     * @param $id
     * @return int
     */
    protected function isBCIDExist($bc_id, $exID = 0)
    {
        if(!$bc_id) return 0;
        $data  = view_supply_user::where('bc_id',$bc_id);
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
        $data = view_supply_user::where('b_cust_id',$uid)->select('b_supply_id')->first();
        return isset($data->b_supply_id)? $data->b_supply_id : 0;
    }
    protected function getSupplyName($uid)
    {
        if(!$uid) return '';
        $data = view_supply_user::where('b_cust_id',$uid)->select('supply')->first();
        return isset($data->supply)? $data->supply : '';
    }

    protected function getProjectID($uid)
    {
        if(!$uid) return 0;
        $data = view_supply_user::where('b_cust_id',$uid)->select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    protected function getData($uid)
    {
        if(!$uid) return 0;
        $data = view_supply_user::where('b_cust_id',$uid)->first();
        return $data;
    }

    protected function getMemeber($supply_id)
    {
        $ret = [];
        $data = view_supply_user::where('b_supply_id',$supply_id)->select('b_cust_id');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[] = $val->b_cust_id;
            }
        }

        return $ret;
    }

    protected function getCoursePassMember($supply_id)
    {
        if(!$supply_id) return [];
        $memberAry = view_supply_user::join('log_course_pass as l','l.b_cust_id','=','view_supply_user.b_cust_id')->
        where('l.sdate',date('Y-m-d'))->where('view_supply_user.b_supply_id',$supply_id);
        $memberAry = $memberAry->select('view_supply_user.b_cust_id','view_supply_user.name')->get()->toArray();

        return $memberAry;
    }

    /**
     * 取得承攬商成員資訊
     * @param int $supply
     * @param int $uid
     * @return array|int
     */
    protected function getSupplyMemberInfo($uid)
    {
        $ret  = [];
        if(!$uid) return $ret;
        $cpcAry       = SHCSLib::getCode('CPC_TAG',0);

        $data = view_supply_user::where('b_cust_id',$uid);
        $data = $data->first();
        if(isset($data->b_cust_id))
        {
            $ret['b_supply_id']         = $data->b_supply_id;
            $ret['supply']              = $data->supply;
            $ret['b_cust_id']           = $data->b_cust_id;
            $ret['name']                = $data->name;
            $ret['e_project_id']        = 0;
            $ret['project']             = '';
            $ret['project_no']          = '';
            $ret['cpc_tag']             = '';
            $ret['cpc_tag_name']        = '';

            $getProject = view_door_supply_member::getData($uid);
            if(isset($getProject->b_cust_id))
            {
                $ret['e_project_id']        = $getProject->e_project_id;
                $ret['project']             = $getProject->project;
                $ret['project_no']          = $getProject->project_no;
                $ret['cpc_tag']             = $getProject->cpc_tag;
                $ret['cpc_tag_name']        = isset($cpcAry[$getProject->cpc_tag])? $cpcAry[$getProject->cpc_tag] : '';
            }
        }
        return $ret;
    }

}
