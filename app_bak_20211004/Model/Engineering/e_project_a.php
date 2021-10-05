<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\WorkPermit\wp_permit_identity;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_a';
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
     *  是否存在[使用者]
     * @param $id
     * @return int
     */
    protected function isExist($e_project_id,$supply_id, $ext_id = 0)
    {
        if(!$e_project_id || !$supply_id) return 0;
        $data  = e_project_a::where('e_project_id',$e_project_id)->where('b_supply_id',$supply_id)->where('isClose','N');
        if($ext_id)
        {
            $data = $data->where('id','!=',$ext_id);
        }
        return $data->count();
    }

    //取得 下拉選擇全部
    protected  function getIDAry($id)
    {
        $ret  = [];
        $data = e_project_a::where('e_project_id',$id)->select('b_supply_id')->where('isClose','N');
        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $ret[] = $val->b_supply_id;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($id,$isMain = 1,$e_supply_type = 0,$isFirst = 1)
    {
        $ret  = [];
        $data = e_project_a::where('e_project_id',$id)->select('b_supply_id')->where('isClose','N');
        if($e_supply_type)
        {
            $data = $data->where('e_supply_type',$e_supply_type);
        }
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        if($isMain)  {
            $sid        = e_project::getSupply($id);
            $ret[$sid]  = b_supply::getName($sid);
        }

        foreach ($data as $key => $val)
        {
            $ret[$val->b_supply_id] = b_supply::getName($val->b_supply_id);
        }

        return $ret;
    }



}
