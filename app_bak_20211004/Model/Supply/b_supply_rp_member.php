<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_member extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_member';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($sid,$aproc = '')
    {
        if(!$sid) return 0;
        $data = b_supply_rp_member::where('b_supply_id',$sid);
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        return $data->count();
    }

    //取得 申請成員姓名
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = b_supply_rp_member::where('id',$id)->select('id','name')->first();
        return isset($data->id)? $data->name : '';
    }

    //取得 承攬商ＩＤ
    protected  function getSupplyID($id)
    {
        if(!$id) return 0;
        $data = b_supply_rp_member::where('id',$id)->select('id','b_supply_id')->first();
        return isset($data->id)? $data->b_supply_id : 0;
    }
    //取得 申請者ＩＤ
    protected  function getApplyUser($id)
    {
        if(!$id) return 0;
        $data = b_supply_rp_member::where('id',$id)->select('id','apply_user')->first();
        return isset($data->id)? $data->apply_user : 0;
    }

    /**
     *  頭像
     * @param $id
     * @return int
     */
    protected function getHeadImg($id)
    {
        if(!$id) return 0;
        $data  = b_supply_rp_member::find($id);
        return isset($data->head_img)? $data->head_img : '';
    }
}
