<?php

namespace App\Model;

use App\Model\View\view_user;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    use Notifiable;
    /**
     * 使用者Table:
     */
    protected $table = 'b_cust';
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
        'name', 'account', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * 是否存在
     * @param $id
     * @return string
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $ret = User::where('id',$id)->where('isClose','N');

        return $ret->count();
    }

    /**
     * 帳號是否已經存在
     * @param $id
     * @return string
     */
    protected function isAccountExist($account,$extid = 0)
    {
        if(!$account) return 0;
        $ret = User::where('account',$account)->where('isClose','N');
        if($extid)
        {
            $ret = $ret->where('id','!=',$extid);
        }
        $data = $ret->first();

        return (isset($data->id))? $data->id : 0;
    }

    /**
     * 帳號是否可登入
     * @param $id
     * @return string
     */
    protected function isLogin($id,$type = 0)
    {
        if(!$id) return 0;
        $param = ($type)? 'account' : 'id';
        $ret = User::where($param,$id)->where('isLogin','Y')->where('isClose','N');

        return $ret->count();
    }

    /**
     * 帳號是否已經作廢
     * @param $id
     * @return string
     */
    protected function isClose($id)
    {
        if(!$id) return 0;
        $ret = User::where('id',$id)->where('isClose','Y');

        return $ret->count();
    }

    protected function getName($id)
    {
        $ret = '';
        if(!$id) return $ret;
        if(is_array($id))
        {
            $data = User::whereIn('id',$id)->select('name');
            if($data->count())
            {
                foreach ($data->get() as $val)
                {
                    if(strlen($ret)) $ret.= '，';
                    $ret .= $val->name;
                }
            }

        } else {
            $data = User::where('id',$id)->select('name')->first();
            $ret  = (isset($data->name))? $data->name : '';
        }

        return $ret;
    }
    /**
     *  姓名+行動電話
     * @param $id
     * @return int
     */
    protected function getMobileInfo($id)
    {
        if(!$id) return ['',''];
        $data  = User::join('b_cust_a as a','a.b_cust_id','=','id')->where('id',$id)->select('name','mobile1')->first();
        return isset($data->mobile1)? [$data->name,$data->mobile1] : ['',''];
    }

    protected function getPushID($id)
    {
        if(!$id) return '';
        $ret = User::where('id',$id)->select('pusher_id')->first();

        return (isset($ret->pusher_id))? $ret->pusher_id : '';
    }

    protected function gePushToID($pushKey)
    {
        if(!$pushKey) return 0;
        $ret = User::where('pusher_id',$pushKey)->where('isClose','N')->select('id')->first();

        return (isset($ret->id))? $ret->id : 0;
    }

    protected function getBcType($id)
    {
        if(!$id) return 0;
        $ret = User::where('id',$id)->select('bc_type')->first();

        return (isset($ret->bc_type))? $ret->bc_type : 0;
    }

    protected function getSignImg($id)
    {
        if(!$id) return '';
        $ret = User::where('id',$id)->select('sign_img')->first();

        return (isset($ret->sign_img))? $ret->sign_img : '';
    }

    protected function getIsIOS($id)
    {
        if(!$id) return 'N';
        $ret = User::where('id',$id)->select('isIOS')->first();

        return (isset($ret->isIOS))? $ret->isIOS : 'N';
    }

    /**
     * 作廢
     * @param $id
     * @return string
     */
    protected function setClose($id, $mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        $data = User::where('id',$id)->where('isClose','N')->first();
        if(isset($data->id))
        {
            $data->isClose       = 'Y';
            $data->close_user    = $mod_user;
            $data->mod_user      = $mod_user;
            $data->close_stamp   = date('Y-m-d H:i:s');
            $ret = $data->save();
        }

        return $ret;
    }
}
