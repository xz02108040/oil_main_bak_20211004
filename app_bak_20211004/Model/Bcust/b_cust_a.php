<?php

namespace App\Model\Bcust;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Session;
use Lang;

class b_cust_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_cust_a';
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

    protected $guarded = ['id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = b_cust_a::where('b_cust_id',$id);
        return $data->count();
    }

    /**
     *  性別
     * @param $id
     * @return int
     */
    protected function getSex($id)
    {
        if(!$id) return 0;
        $data = b_cust_a::where('b_cust_id',$id)->select('sex')->first();
        return isset($data->sex)? $data->sex : 'N';
    }

    protected function getBcID($id)
    {
        if(!$id) return '';
        $data = b_cust_a::where('b_cust_id',$id)->select('bc_id')->first();

        return (isset($data->bc_id))? $data->bc_id : '';
    }

    protected function getMobile($id)
    {
        if(!$id) return '';
        $data = b_cust_a::where('b_cust_id',$id)->select('mobile1')->first();

        return (isset($data->mobile1))? $data->mobile1 : '';
    }

    protected function getAddress($id)
    {
        if(!$id) return '';
        $data = b_cust_a::where('b_cust_id',$id)->select('addr1')->first();

        return (isset($data->addr1))? $data->addr1 : '';
    }

    /**
     *  頭像
     * @param $id
     * @return int
     */
    protected function getHeadImg($id, $replyType = 1)
    {
        if(!$id) return '';
        $data = b_cust_a::where('b_cust_id',$id)->select('head_img')->first();
        if(!isset($data->head_img)) return '';

        //回傳base64encode
        if($replyType == 2)
        {
            $img_path = ($data->head_img)? storage_path('app'.$data->head_img) : '';
            return ($img_path && file_exists($img_path))? base64_encode(file_get_contents($img_path)) : '';
        } else {
            return $data->head_img;
        }
    }

    //更新帳號時，同步更新帳號明細檔的身分證字號欄位
    protected function update_bc_id($id)
    {
        $ret = false;

        if (!$id) return '';
        $User_data = User::find($id);
        $data = b_cust_a::where('b_cust_id', $id)->first();

        if ($User_data->account != $data->bc_id) {
            $INS = $data;
            $INS->bc_id = isset($User_data->account) ? $User_data->account : $data->bc_id;
            $INS->mod_user          = Session::get('user.b_cust_id');
            $ret = $INS->save();
        }

        return $ret;
    }

    /**
     *  清除緊急聯絡人相關欄位
     * @param $id
     * @return int
     */
    protected function closeKin($id ,$mod_user = 1)
    {
        $ret = false;

        if (!$id) return $ret;
        $UPD = b_cust_a::find($id);
        $isUp = 0;

        if(isset($UPD->kin_tel)){
            $isUp++;
            $UPD->kin_user = '';
        }
        if(isset($UPD->kin_kind)){
            $isUp++;
           $UPD->kin_kind = '';
        }
        if(isset($UPD->kin_tel)){
            $isUp++;
            $UPD->kin_tel = '';
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

}
