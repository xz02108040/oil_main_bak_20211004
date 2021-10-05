<?php

namespace App\Model\Factory;

use Illuminate\Database\Eloquent\Model;
use Lang;

class b_factory extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_factory';
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
        $data  = b_factory::where('id',$id)->where('isClose','N');
        return $data->count();
    }

    /**
     *  廠區名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = b_factory::where('id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }

    /**
     *  廠區電話
     * @param $id
     * @return int
     */
    protected function getTel($id)
    {
        if(!$id) return '';
        $data  = b_factory::where('id',$id)->select('tel1')->first();
        return isset($data->tel1)? $data->tel1 : '';
    }

    /**
     * 取得廠區 下拉選擇全部
     * @param int $isFirst 下拉選單 [請選擇]
     * @param string $isHasLocal 是否僅列出 有場地的廠區
     * @return array
     */
    protected  function getSelect($isFirst = 1,$isHasLocal = '')
    {
        $ret  = [];
        $data = b_factory::select('id','name')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_40109');

        foreach ($data as $key => $val)
        {
            $isOk = 'Y';
            if($isHasLocal == 'Y' && !b_factory_a::hasExist($val->id))
            {
                $isOk = 'N';
            }
            if($isOk == 'Y') $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部 [廠區->場地]
    protected  function getApiSelect($isLocal = 0)
    {
        $ret  = [];
        $data = b_factory::select('id','name')->where('isClose','N')->get();

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']      = $val->id;
            $tmp['name']    = $val->name;
            if($isLocal) $tmp['detail']  = b_factory_a::getApiSelect($val->id);
            $ret[] = $tmp;
        }

        return $ret;
    }

}
