<?php

namespace App\Model\Factory;

use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_factory_a extends Model
{
    use FactoryDeviceTrait;
    /**
     * 使用者Table:
     */
    protected $table = 'b_factory_a';
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
    protected function isExist($store_id, $localid)
    {
        if(!$localid) return 0;
        $data  = b_factory_a::where('id',$localid)->where('isClose','N');
        if($store_id)
        {
            $data = $data->where('b_factory_id',$store_id);
        }
        return $data->count();
    }

    /**
     *  該廠區是否存在場地
     * @param $id
     * @return int
     */
    protected function hasExist($store_id)
    {
        if(!$store_id) return 0;
        $data  = b_factory_a::where('b_factory_id',$store_id)->where('isClose','N');
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = b_factory_a::where('id',$id)->select('name')->first();
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
        $data  = b_factory_a::where('id',$id)->select('b_factory_id')->first();
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
        $data  = b_factory_a::find($id);
        return isset($data->b_factory_id)? [$data->name,$data->GPSX,$data->GPSY] : ['',0,0];
    }

    //取得 下拉選擇全部
    protected  function getSelect($store = 0, $kind = 0, $isFirst = 1)
    {
        $ret  = [];
        $data = b_factory_a::select('id','name')->where('isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($kind)
        {
            $data = $data->where('kind',$kind);
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
    protected  function getApiSelect($store = 0,$isFirst = 1,$isDetial = 'N')
    {
        $ret  = [];
        $data = b_factory_a::select('id','b_factory_id','name')->where('isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }

        $data = $data->get();

        if($isFirst) $ret[0] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015'),'b_factory_id'=>0];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;
            $tmp['b_factory_id']    = $val->b_factory_id;
            //施工地點
            if($isDetial == 'Y')
            {
                $tmp['works_area'] = $this->getApiFactoryDeviceReply($val->id);
                $tmp['door']       = b_factory_d::getApiSelect($val->b_factory_id,0);
            }
            $ret[] = $tmp;
        }

        return $ret;
    }

}
