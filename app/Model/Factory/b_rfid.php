<?php

namespace App\Model\Factory;

use Illuminate\Database\Eloquent\Model;
use Lang;

class b_rfid extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_rfid';
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
    protected function isExist($name = '' , $code = '', $extid = 0)
    {
        if(!$name && !$code) return 0;
        $data  = b_rfid::where('isClose','N');
        if($name)
        {
            $data =$data->where('name',$name);
        }
        if($code)
        {
            $data =$data->where('rfid_code',$code);
        }
        if($extid)
        {
            $data =$data->where('id','!=',$extid);
        }
        $data = $data->select('id')->first();

        return isset($data->id)? $data->id : 0;
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isClose($id = 0 , $code = '')
    {
        if(!$id && !$code) return 0;
        $data  = b_rfid::where('isClose','Y');
        if($id)
        {
            $data =$data->where('id',$id);
        }
        if($code)
        {
            $data =$data->where('rfid_code',$code);
        }
        return $data->count();
    }

    /**
     *  是否正被使用
     * @param $id
     * @return int
     */
    protected function isUsed($id = 0 , $code = '')
    {
        if(!$id && !$code) return 0;
        $data  = b_rfid::where('isUsed','Y')->where('isClose','N');

        if($id)
        {
            $data =$data->where('id',$id);
        }
        if($code)
        {
            $data =$data->where('rfid_code',$code);
        }
        return $data->count();
    }

    /**
     *  卡片內碼
     * @param $id
     * @return int
     */
    protected function getCode($id)
    {
        if(!$id) return '';
        $data  = b_rfid::find($id);
        return isset($data->rfid_code)? $data->rfid_code : '';
    }

    /**
     *  用卡片內碼查詢ＩＤ
     * @param $id
     * @return int
     */
    protected function getID($code)
    {
        if(!$code) return 0;
        $data  = b_rfid::where('rfid_code',$code)->where('isClose','N')->select('id')->first();
        return isset($data->id)? $data->id : 0;
    }

    /**
     *  卡片類型
     * @param $id
     * @return int
     */
    protected function getType($id)
    {
        if(!$id) return 0;
        $data  = b_rfid::find($id);
        return isset($data->rfid_type)? $data->rfid_type : 0;
    }

    /**
     *  廠區
     * @param $id
     * @return int
     */
    protected function getStore($id)
    {
        if(!$id) return 0;
        $data  = b_rfid::find($id);
        return isset($data->b_factory_id)? $data->b_factory_id : 0;
    }

    /**
     *  目前正在使用的配對
     * @param $id
     * @return int
     */
    protected function getUsedId($id)
    {
        if(!$id) return 0;
        $data  = b_rfid::find($id);
        return isset($data->b_rfid_a_id)? $data->b_rfid_a_id : 0;
    }

    /**
     *  取得 配對的場地ＩＤ
     * @param $id
     * @return int
     */
    protected function getLocalRFID($b_factory_a_id,$code)
    {
        if(!$code || !$b_factory_a_id) return 0;
        //dd([$code,$b_factory_a_id]);
        $data  = b_rfid::join('b_rfid_a as a','a.id','=','b_rfid.b_rfid_a_id')->
                where('b_rfid.rfid_code',$code)->where('b_rfid.isClose','N')->
                where('a.b_factory_a_id',$b_factory_a_id)->where('a.isClose','N')->
                select('a.id')->first();
        return isset($data->id)? $data->id : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isUsed = 'N',$isFirst = 1)
    {
        $ret  = [];
        $data = b_rfid::select('id','rfid_code')->where('isUsed',$isUsed)->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->rfid_code;
        }

        return $ret;
    }

}
