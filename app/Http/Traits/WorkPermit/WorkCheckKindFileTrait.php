<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_check_kind_f;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_workitem;

/**
 * 檢點單 類型
 *
 */
trait WorkCheckKindFileTrait
{
    /**
     * 新增 檢點單 類型
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkCheckKindFile($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_check_kind_f();
        $INS->wp_check_kind_id      = $data->wp_check_kind_id;
        $INS->name                  = $data->name;
        $INS->path                  = $data->path;
        $INS->show_order            = is_numeric($data->show_order)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改檢點單 類型
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkCheckKindFile($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_check_kind_f::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
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
     * 取得 檢點單 類型
     *
     * @return array
     */
    public function getApiWorkCheckKindFileList($kid)
    {
        $ret = array();
        $kindAry = wp_check_kind::getSelect(0);
        //取第一層
        $data = wp_check_kind_f::where('wp_check_kind_id',$kid);
        $data = $data->orderby('isClose')->orderby('show_order')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['kind']        = isset($kindAry[$v->wp_check_kind_id])? $kindAry[$v->wp_check_kind_id] : '';
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
