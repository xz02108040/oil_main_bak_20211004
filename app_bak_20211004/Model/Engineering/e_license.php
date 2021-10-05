<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class e_license extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_license';
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
        $data  = e_license::where('id',$id);
        return $data->count();
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isNameExist($name)
    {
        if(!$name) return 0;
        $data  = e_license::where('name',$name);//->where('isClose','N');
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
        $data  = e_license::find($id);
        return isset($data->name)? $data->name : '';
    }
    /**
     *  回傳：顯示證號名稱，顯示日期名稱，日期判斷
     * @param $id
     * @return int
     */
    protected function getShowList($id)
    {
        if(!$id) return ['','','','',''];
        $data  = e_license::find($id);
        return isset($data->name)? [$data->license_show_name1,$data->license_show_name2,$data->license_show_name3,$data->license_show_name4,$data->license_show_name5,$data->edate_type] : ['','','','',''];
    }
    /**
     *  回傳：
     * @param $id
     * @return int
     */
    protected function getIssuingList($id)
    {
        if(!$id) return [1,0,0];
        $data  = e_license::find($id);
        return isset($data->name)? [$data->license_issuing_kind,$data->edate_limit_year1,$data->edate_limit_year2] : [1,0,0];
    }

    /**
     *  類型
     * @param $id
     * @return int
     */
    protected function getTypeId($id)
    {
        if(!$id) return 0;
        $data  = e_license::find($id);
        return isset($data->license_type)? $data->license_type : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($type = 0, $isFirst = 1, $extAry = [])
    {
        $ret  = [];
        $data = e_license::select('id','name')->where('isClose','N');

        if($type)
        {
            $data = $data->where('license_type',$type);
        }
        if(count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }



}
