<?php

namespace App\Model\Factory;

use Illuminate\Database\Eloquent\Model;
use Lang;

class b_factory_b extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_factory_b';
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
        $data  = b_factory_b::where('id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isLocalExist($b_factory_a_id,$id)
    {
        if(!$b_factory_a_id || !$id) return 0;
        $data  = b_factory_b::where('id',$id)->where('b_factory_a_id',$b_factory_a_id)->where('isClose','N');
        return $data->count();
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isIDCodeExist($id_code)
    {
        if(!$id_code) return 0;
        $data  = b_factory_b::where('id_code',$id_code)->where('isClose','N')->first();
        return isset($data->b_factory_a_id)? $data->b_factory_a_id : 0;
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = b_factory_b::where('id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }
    /**
     *  廠區ＩＤ
     * @param $id
     * @return int
     */
    protected function getStoreId($id)
    {
        if(!$id) return 0;
        $data  = b_factory_b::where('id',$id)->select('b_factory_id')->first();
        return isset($data->b_factory_id)? $data->b_factory_id : 0;
    }
    /**
     *  GPS
     * @param $id
     * @return int
     */
    protected function getGPS($id)
    {
        if(!$id) return ['',0,0];
        $data  = b_factory_b::where('id',$id)->select('name','GPSX','GPSY')->first();
        return isset($data->name)? [$data->name,$data->GPSX,$data->GPSY] : ['',0,0];
    }
    /**
     *  廠區ＩＤ
     * @param $id
     * @return int
     */
    protected function getLocalId($id_code,$ip)
    {
        if(!$id_code || !$ip) return 0;
        $data  = b_factory_b::where('id_code',$id_code)->where('isClose','N')->first();
        return isset($data->b_factory_a_id)? [$data->b_factory_a_id,$data->id] : [0,0];
    }

    //取得 下拉選擇全部
    protected  function getSelect($sid = 0,$aid = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_b::select('id','name')->where('isClose','N');
        if($sid)
        {
            $data = $data->where('b_factory_id',$sid);
        }
        if($aid)
        {
            $data = $data->where('b_factory_a_id',$aid);
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
    protected  function getApiSelect($local = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_b::select('id','b_factory_a_id','name')->where('isClose','N');
        if($local)
        {
            $data = $data->where('b_factory_a_id',$local);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_a_id'=>0];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;
            $tmp['b_factory_a_id']  = $val->b_factory_a_id;
            $ret[] = $tmp;
        }

        return $ret;
    }

}
