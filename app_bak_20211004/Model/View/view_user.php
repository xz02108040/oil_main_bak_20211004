<?php

namespace App\Model\View;

use Illuminate\Database\Eloquent\Model;
use Lang;

class view_user extends Model
{
    /**
     * 使用者Table: 列出沒有作廢的會員帳號＆資料
     */
    protected $table = 'view_user';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_cust_id';

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

    protected $guarded = ['b_cust_id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = view_user::where('b_cust_id',$id);
        return $data->count();
    }


    /**
     *  是否存在[身分證]
     * @param $id
     * @return int
     */
    protected function isBCIDExist($bc_id, $exID = 0, $bctype = 0, $supply_id = 0)
    {
        if(!$bc_id) return 0;
        $data  = view_user::where('view_user.bc_id',$bc_id);
        if($supply_id)
        {
            $data = $data->join('b_supply_member as sm','sm.b_cust_id','=','view_user.b_cust_id');
            $data = $data->join('b_supply as s','s.id','=','sm.b_supply_id');
            $data = $data->where('s.id',$supply_id);
            $data = $data->where('s.isClose','N');
        }
        if($exID)
        {
            $data = $data->where('view_user.b_cust_id','!=',$exID);
        }
        if($bctype)
        {
            $data = $data->where('view_user.bc_type',$bctype);
        }
        $data = $data->select('view_user.b_cust_id')->first();

        return isset($data->b_cust_id)? $data->b_cust_id : 0;
    }

    /**
     *  姓名
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = view_user::where('b_cust_id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }
    /**
     *  姓名
     * @param $id
     * @return int
     */
    protected function getBcType($id)
    {
        if(!$id) return 0;
        $data  = view_user::find($id);
        return isset($data->bc_type)? $data->bc_type : 0;
    }

    /**
     *  性別
     * @param $id
     * @return int
     */
    protected function getSex($id)
    {
        if(!$id) return 'N';
        $data  = view_user::find($id);
        return isset($data->sex)? $data->sex : 'N';
    }

    /**
     *  頭像
     * @param $id
     * @return int
     */
    protected function getHeadImg($id)
    {
        if(!$id) return '';
        $data  = view_user::find($id);
        return isset($data->head_img)? $data->head_img : '';
    }

    /**
     *  推播ＩＤ
     * @param $id
     * @return int
     */
    protected function getPushID($id)
    {
        if(!$id) return '';
        $data  = view_user::find($id);
        return isset($data->pusher_id)? $data->pusher_id : '';
    }

    /**
     *  推播ＩＤ
     * @param $id
     * @return int
     */
    protected function SearchBCID($bcid)
    {
        if(!$bcid) return '';
        $data  = view_user::where('bc_id','like','%'.$bcid.'%')->select('b_cust_id')->first();
        return isset($data->b_cust_id)? $data->b_cust_id : 0;
    }



}
