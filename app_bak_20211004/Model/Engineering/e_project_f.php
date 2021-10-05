<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_f extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_f';
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
    protected function isExist($project_id,$factory_id = 0)
    {
        if(!$project_id) return 0;
        $data  = e_project_f::where('e_project_id',$project_id)->where('isClose','N');
        if($factory_id)
        {
            $data = $data->where('b_factory_id',$factory_id);
        }
        return $data->count();
    }

    /**
     *  取得工程案件之負責廠區名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $storeAry = [];

        $data  = e_project_f::
                join('b_factory_a as f','f.id','=','e_project_f.b_factory_a_id')->
                where('e_project_f.e_project_id',$id)->where('e_project_f.isClose','N')->select('f.name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = $val->name;
            }
        }

        return implode('，',$storeAry);
    }
    /**
     *  取得工程案件之負責廠區名稱
     * @param $id
     * @return int
     */
    protected function genBtn($project_id)
    {
        if(!$project_id) return '';
        $storeAry = [];

        $data  = e_project_f::
                join('b_factory_a as f','f.id','=','e_project_f.b_factory_a_id')->
                where('e_project_f.e_project_id',$project_id)->where('e_project_f.isClose','N')->select('f.name')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = HtmlLib::btn('#',$val->name,7);
            }
        }

        return implode(' ',$storeAry);
    }

    //取得 下拉選擇全部
    protected  function getStoreList($id,$color = 1)
    {
        $retAry  = [];
        if(!$id) return '';
        $color = $color ? $color : 6;

        $data = e_project_f::where('e_project_id',$id)->select('b_factory_id')->where('isClose','N');

        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $retAry[$val->b_cust_id] = HtmlLib::btn('#',User::getName($val->b_cust_id),$color);
        }

        return implode('，',$retAry);
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$store_id = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = e_project_f::where('e_project_id',$project_id)->select('b_factory_a_id')->where('isClose','N');
        if($store_id)
        {
            $data = $data->where('b_factory_id',$store_id);
        }

        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->b_factory_a_id] = b_factory_a::getName($val->b_factory_a_id);
        }

        return $ret;
    }



}
