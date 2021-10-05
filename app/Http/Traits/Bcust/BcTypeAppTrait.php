<?php

namespace App\Http\Traits\Bcust;

use App\Model\b_cust_a;
use App\Model\b_cust_p;
use App\Model\b_menu_group;
use App\Model\bc_type_app;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\v_cust;
use App\Model\v_stand;
use Hash;

/**
 * ＡＰＰ身分 對應 帳號身分.
 * User: dorado
 *
 */
trait BcTypeAppTrait
{
    /**
     * 新增 ＡＰＰ身分 對應
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBcTypeApp($data,$mod_user = 1)
    {
        $ret = false;
        $b_cust_id = 0;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new bc_type_app();
        $INS->bc_type           = $data->bc_type;
        $INS->name              = $data->name;
        $INS->show_order        = $data->show_order;
        $INS->new_user          = $mod_user;
        $INS->mod_user          = $mod_user;

        //如果新增成功
        if($INS->save())
        {
            $b_cust_id = $INS->id;
        }


        return $b_cust_id;
    }

    /**
     * 修改 ＡＰＰ身分 對應
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBcTypeApp($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = bc_type_app::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        if(isset($data->bc_type) && $data->bc_type && $data->bc_type !== $UPD->bc_type)
        {
            $isUp++;
            $UPD->bc_type = $data->bc_type;
        }
        if(isset($data->show_order) && $data->show_order && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose = $data->isClose;
                $UPD->close_user = $mod_user;
                $UPD->close_stamp = $now;
            } else {
                $UPD->isClose = 'N';
            }
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

    /**
     * 取得 ＡＰＰ身分 對應
     *
     * @return array
     */
    public function getApiBcTypeAppList($bctype = '')
    {
        $ret = array();
        $bctypeAry  = SHCSLib::getCode('BC_TYPE');
        //取第一層
        $data = bc_type_app::where('bc_type',$bctype)->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['bc_type_name']   = (isset($bctypeAry[$v->bc_type]))? $bctypeAry[$v->bc_type] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
