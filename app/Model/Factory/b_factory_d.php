<?php

namespace App\Model\Factory;

use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_factory_d extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_factory_d';
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
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = b_factory_d::where('id',$id)->where('isClose','N');
        return $data->count();
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isIDCodeExist($door_account)
    {
        if(!$door_account) return 0;
        $data  = b_factory_d::where('door_account',$door_account)->where('isClose','N')->first();
        return isset($data->id)? $data->id : 0;
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = b_factory_d::where('id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }
    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getActStoreId($door_account)
    {
        if(!$door_account) return 0;
        $data  = b_factory_d::where('door_account',$door_account)->where('isClose','N')->select('b_factory_id')->first();
        return isset($data->b_factory_id)? $data->b_factory_id : 0;
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getActPwd($id)
    {
        if(!$id) return ['',''];
        $data  = b_factory_d::find($id);
        return isset($data->name)? [$data->door_account,$data->door_pwd] : ['',''];
    }
    /**
     *  利用帳密得到 門禁資訊
     * @param $id
     * @return int
     */
    protected function checkDoorInfo($account,$pwd)
    {
        if(!$account || !$pwd) return [0,0,'',0];
        $data  = b_factory_d::where('door_account',$account)->where('door_pwd',$pwd)->where('isClose','N')->first();
        return isset($data->b_factory_id)? [$data->b_factory_id,$data->id,$data->name,$data->door_type] : [0,0,'',0];
    }

    /**
     *  取得門禁規則
     * @param $id
     * @return int
     */
    protected function getDoorType($id)
    {
        $def_door_rule_kind = sys_param::getParam('DOOR_RULE_KIND','1');
        if(!$id) return $def_door_rule_kind;

        $data  = b_factory_d::where('id',$id)->select('door_type')->where('isClose','N')->first();
        return (isset($data->door_type) && $data->door_type)? $data->door_type : $def_door_rule_kind;
    }
    /**
     *  廠區ＩＤ
     * @param $id
     * @return int
     */
    protected function getStoreId($id)
    {
        if(!$id) return 0;
        $data  = b_factory_d::where('id',$id)->select('b_factory_id')->where('isClose','N')->first();
        return isset($data->b_factory_id)? $data->b_factory_id : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($sid = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_d::select('id','name')->where('isClose','N');
        if($sid)
        {
            $data = $data->where('b_factory_id',$sid);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getApiSelect($store = 0,$isFirst = 1,$showType = 1)
    {
        $ret  = [];
        $data = b_factory_d::select('id','b_factory_id','name','door_account')->where('isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = ($showType == 2)? ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_id'=>0,'door_account'=>''] : ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_id'=>0];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;
            $tmp['b_factory_id']    = $val->b_factory_id;
            if($showType == 2)
            {
                $tmp['door_account']    = $val->door_account;
            }
            $ret[] = $tmp;
        }

        return $ret;
    }
}
