<?php

namespace App\Model\Supply;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_s;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\WorkPermit\wp_permit_identity;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_member_ei extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_member_ei';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($uid, $engineering_identity_id, $exid = 0)
    {
        if(!$uid || !$engineering_identity_id) return 0;
        $data = b_supply_member_ei::where('b_cust_id',$uid)->where('engineering_identity_id',$engineering_identity_id)->
                where('isClose','N')->where('edate','>=',date('Y-m-d'));
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }
        //dd([$uid,$tid,$exid,$data->count()]);
        return $data->count();
    }

    //是否存在
    protected  function getEdate($uid, $engineering_identity_id)
    {
        if(!$uid || !$engineering_identity_id) return '';
        $data = b_supply_member_ei::where('b_cust_id',$uid)->where('engineering_identity_id',$engineering_identity_id)->
        where('isClose','N')->where('edate','>=',date('Y-m-d'));
        $data = $data->first();
        //dd([$uid,$tid,$exid,$data->count()]);
        return isset($data->id)? $data->edate : '';
    }

    /**
     * 取得 工程身份名稱
     * @param $id
     * @return string
     */
    protected function getName($id)
    {
        $data = b_supply_member_ei::find($id);

        return isset($data->engineering_identity_id)? b_supply_engineering_identity::getName($data->engineering_identity_id) : '';
    }

    /**
     * 取得 該成員之所有工程身份名稱
     * @param $id
     * @return string
     */
    /*
    protected function getUserIdentityAllName($uid, $allowIdentityAry = [], $isApi = 0)
    {
        $ret = ($isApi)? [] : '';
        if(!$uid) return $ret;
        $data = b_supply_member_ei::where('b_cust_id',$uid)->where('edate','>=',date('Y-m-d'))->
                select('engineering_identity_id','edate')->where('isClose','N')->get();
        if(count($data))
        {
            $tmp = [];
            foreach ($data as $val)
            {
                $isOk = 1;
                //只列出 允許的工程身份
                if (count($allowIdentityAry))
                {
                    if(!isset($allowIdentityAry[$val->engineering_identity_id]))
                    {
                        $isOk = 0;
                    }
                }
                if($isOk)
                {
                    $identity = b_supply_engineering_identity::getName($val->engineering_identity_id);
                    if($isApi)
                    {
                        $tmp[$val->engineering_identity_id]['name'] = $identity.'('.$val->edate.')';
                    } else {
                        $tmp[$val->engineering_identity_id] = HtmlLib::Color($identity,'blue',1).'('.$val->edate.')';
                    }
                }
            }
            $ret = ($isApi)? $tmp : implode('<br/>',$tmp);
        }
        return $ret;
    }
    */

    //取得 符合特定工作許可證之工作身分
    protected  function getIdentityMemberApi($supply,$pid = 0,$iid = 0, $isApi = 1)
    {
        $ret = [];
        if(!$supply) return $ret;

        //1. 取得 該工程案件之成員
        $menAry     = view_door_supply_member::getProjectMemberSelect($pid,[],0,0,0);
        //2. 取得 工作許可證 指定之工程身份 <排除 工安＆工負>
        $idAry      = e_project_l::getSelect($pid,1,0);
        //3. 取得 該承商之成員 擁有該工作許可證人員
        $memberAry  = b_supply_member_ei::getIdentitySelect($supply,$iid,0,1,array_keys($menAry));
//        dd($supply,$pid,($menAry),$idAry,$memberAry);
        unset($memberAry[1]);
        unset($memberAry[2]);
        foreach ($idAry as $id => $name)
        {
            if(isset($memberAry[$id]))
            {
                if($isApi)
                {
                    $tmp = [];
                    $tmp['id']       = $id;
                    $tmp['name']     = $name;
                    $tmp['member']   = $memberAry[$id];
                    $ret[] = $tmp;
                } else {
                    $ret[$id] = $name;
                }
            }
        }

        return $ret;
    }

    /**
     * 取得 特定人員之工程身份<生效中>
     * @param int $uid
     * @param int $isFirst
     * @return array
     */
    protected  function getMemberSelect($uid = 0,$isFirst = 1)
    {
        $ret    = [];
        if(!$uid) return $ret;

        $data   = b_supply_member_ei::where('b_cust_id',$uid)->where('edate','>=',date('Y-m-d'))->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->engineering_identity_id] = b_supply_engineering_identity::getName($val->engineering_identity_id);
        }

        return $ret;
    }

    /**
     * 取得 承攬商之擁有指定工程身份 成員
     * @param $sid
     * @param $iid
     * @param int $isFirst
     * @param int $isApi
     * @return array
     */
    protected  function getIdentityMemberSelect($sid , $iid,$isFirst = 1,$isApi = 0)
    {
        $ret    = [];
        if(!$sid) return $ret;

        $data   = b_supply_member_ei::where('b_supply_id',$sid)->where('edate','>=',date('Y-m-d'))->where('isClose','N');
        $data   = $data->where('engineering_identity_id',$iid);
        $data   = $data->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']   = $val->b_cust_id;
                $tmp['name'] = User::getName($val->b_cust_id);
            } else {
                $tmp = $val->b_cust_id;
            }
            $ret[] = $tmp;
        }

        return $ret;
    }

    /**
     * 取得 承攬商之成員 工程身份<且符合 指定人員>
     * @param int $sid
     * @param int $isFirst
     * @param int $isApi
     * @param array $inAry
     * @return array
     */
    protected  function getIdentitySelect($sid = 0, $iis = 0,$isFirst = 1,$isApi = 0,$inUserAry = [])
    {
        $ret    = [];
        if(!$sid) return $ret;

        $data  = b_supply_member_ei::where('b_supply_member_ei.b_supply_id',$sid)->where('b_supply_member_ei.edate','>=',date('Y-m-d'))->where('b_supply_member_ei.isClose','N');
        $data  = $data->join('view_door_supply_whitelist_pass as v','v.b_cust_id','=','b_supply_member_ei.b_cust_id');
        if($iis)
        {
            $data = $data->where('b_supply_member_ei.engineering_identity_id',$iis);
        }
        if(is_array($inUserAry) && count($inUserAry))
        {
            $data = $data->whereIn('b_supply_member_ei.b_cust_id',$inUserAry);
        }
        $data = $data->select('b_supply_member_ei.engineering_identity_id','v.b_cust_id','v.name');
        $data = $data->groupby('b_supply_member_ei.engineering_identity_id')->groupby('v.b_cust_id');
        $data = $data->groupby('v.name')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']   = $val->b_cust_id;
                $tmp['name'] = $val->name;
                $ret[$val->engineering_identity_id][] = $tmp;
            } else {
                $ret[$val->b_cust_id] = $val->name;
            }
        }

        return $ret;
    }
}
