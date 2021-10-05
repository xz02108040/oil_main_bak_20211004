<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_l extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_l';
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
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($project_id , $identity_id, $extid = 0)
    {
        if(!$project_id || !$identity_id) return 0;
        $data  = e_project_l::where('e_project_id',$project_id)->where('engineering_identity_id',$identity_id)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    /**
     * 該成員 是否具有該工程案件之工程身份之一
     * @param $pid
     * @param $uid
     */
    protected function hasAllowIdentity($pid,$uid)
    {
        $ret = 0;
        $projectIdentityAry = e_project_l::getSelect($pid,1,0);
        $myIdentityAry      = b_supply_member_ei::getMemberSelect($uid,0);

        if(count($myIdentityAry))
        {
            foreach ($myIdentityAry as $iid => $uid)
            {
                if(isset($projectIdentityAry[$iid]))
                {
                    $ret++;
                }
            }
        }
        return $ret;
    }

    //TODO 要回來改
    protected  function getAry($project_id)
    {
        $ret  = [];
        $data = e_project_l::where('e_project_id',$project_id)->
                select('id','engineering_identity_id')->where('isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[] = $val->engineering_identity_id;
            }
        }
        return $ret;
    }

    //取得 工程案件之基本工程身分人數
    protected  function getAllName($project_id,$isShowMenAmt = 0)
    {
        $ret  = [];
        $retStr = '';
        $cpcTagAry     = [1=>['A'],2=>['B'],'9'=>['C','D']];
        $identityParam = sys_param::getParam('SUPPLY_IDENTITY_TYPE_EDIT_LIMIT');
        $identityAry   = explode(',',$identityParam);

        foreach ($identityAry as $key => $identity_id)
        {
            $ret[$identity_id] = $identity_id;
        }
        if(count($ret))
        {
            foreach ($ret as $identity_id => $val)
            {
                if($retStr) $retStr .= '<br/>';
                $name    = b_supply_engineering_identity::getName($identity_id);
                //顯示指定工程案件之工程身份人數
                if($isShowMenAmt)
                {
                    $amt  = view_door_supply_member::getCPCTagAmt($project_id,$cpcTagAry[$identity_id]);
                    if(!$amt) $amt = HtmlLib::Color($amt,'red',1);
                    $name      .= Lang::get('sys_base.base_10141',['name'=>$amt]);
                }
                $retStr .= in_array($key,$identityAry)? HtmlLib::Color($name,'blue') : $name;
            }
        }

        return $retStr;
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$isAddBase = 1 ,$isFirst = 1,$extAry = [])
    {
        $ret  = [];
        $data = e_project_l::
        join('b_supply_engineering_identity as e','e.id','=','e_project_l.engineering_identity_id')->
        where('e_project_l.e_project_id',$project_id)->where('e_project_l.isClose','N')->
        select('e_project_l.id','e_project_l.engineering_identity_id','e.name');

        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        //加入 工負＆公安＆施工人員
        if($isAddBase)
        {
            $identityParam = sys_param::getParam('IDENTITY_BASE_ID');
            $identityAry   = explode(',',$identityParam);
            if(count($identityAry))
            {
                foreach ($identityAry as $val)
                {
                    if(!in_array($val,$extAry)) {
                        $ret[$val] = b_supply_engineering_identity::getName($val);
                    }
                }
            }
        }
        foreach ($data as $key => $val)
        {
            if(!in_array($val->engineering_identity_id,$extAry))
            {
                $ret[$val->engineering_identity_id] = $val->name;
            }
        }

        return $ret;
    }



}
