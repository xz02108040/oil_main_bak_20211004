<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_member extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_member';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_cust_id';

    protected $guarded = ['b_cust_id'];

    //是否存在
    protected  function isExist($sid,$uid)
    {
        if(!$sid && !$uid) return 0;
        $data = b_supply_member::where('b_supply_id',$sid)->where('b_cust_id',$uid);
        return $data->count();
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        return User::find($id)->name;
    }

    //取得 名稱
    protected  function getSupplyId($uid)
    {
        if(!$uid) return 0;
        $data = b_supply_member::where('b_cust_id',$uid)->select('b_supply_id')->first();
        return isset($data->b_supply_id)? $data->b_supply_id : 0;
    }


    //取得 該廠商所有成員
    protected  function getArray($sid)
    {
        $ret = [];
        if(!$sid) return $ret;
        $data = b_supply_member::where('b_supply_id',$sid)->get();
        foreach ($data as $val)
        {
            $ret[] = $val->b_cust_id;
        }
        return $ret;
    }

    //取得 該廠商所有成員 作廢
    protected  function setClose($sid , $mod_user = 1)
    {
        $ret = 0;
        if(!$sid) return $ret;
        $data = b_supply_member::where('b_supply_id',$sid)->get();
        foreach ($data as $val)
        {
            if(User::setClose($val->b_cust_id,$mod_user))
            {
                $ret++;
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($sid, $isShow = 0, $isLogin = '' ,$isFirst = 1)
    {
        $ret    = [];
        $data   = b_supply_member::join('view_user as a','b_supply_member.b_cust_id','=','a.b_cust_id')->
        select('a.b_cust_id as id','a.name','a.bc_id');
        $data = $data->where('b_supply_member.b_supply_id',$sid);
        if($isLogin && in_array($isLogin,['Y','N']))
        {
            $data = $data->where('a.isLogin',$isLogin);
        }
        $data   = $data->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name. ($isShow? '('.$val->bc_id.')' : '');
        }

        return $ret;
    }

    
    //取得 該成員所有的承攬商下拉選擇全部
    protected  function getSupplySelect($b_cust_id, $isFirst = 1)
    {
        $ret    = [];
        $data   = b_supply_member::join('b_supply as a','b_supply_member.b_supply_id','=','a.id')->
        select('a.id as id','a.name');
        $data = $data->where('b_supply_member.b_cust_id',$b_cust_id);

        $data   = $data->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
