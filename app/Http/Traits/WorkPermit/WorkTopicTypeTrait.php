<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_topic_type;

/**
 * 工作許可證＿檢核項目類別
 *
 */
trait WorkTopicTypeTrait
{
    /**
     * 新增 工作許可證＿檢核項目類別
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitTopicType($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_topic_type();
        $INS->name          = $data->name;
        $INS->isOption      = in_array($data->isOption,['Y','N'])? $data->isOption : 'N';
        $INS->isPermit      = in_array($data->isPermit,['Y','N'])? $data->isPermit : 'Y';
        $INS->isCheck       = in_array($data->isCheck,['Y','N'])? $data->isCheck : 'Y';
        $INS->show_order    = $data->show_order > 0? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證＿檢核項目類別
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitTopicType($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_topic_type::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //是否需要檢核選項
        if(isset($data->isOption) && in_array($data->isOption,['Y','N']) && $data->isOption !== $UPD->isOption)
        {
            $isUp++;
            $UPD->isOption = $data->isOption;
        }
        //
        if(isset($data->isPermit) && in_array($data->isPermit,['Y','N']) && $data->isPermit !== $UPD->isPermit)
        {
            $isUp++;
            $UPD->isPermit = $data->isPermit;
        }
        //
        if(isset($data->isCheck) && in_array($data->isCheck,['Y','N']) && $data->isCheck !== $UPD->isCheck)
        {
            $isUp++;
            $UPD->isCheck = $data->isCheck;
        }
        //排序
        if(isset($data->show_order) && $data->show_order > 0 && $data->show_order !== $UPD->show_order)
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
     * 取得 工作許可證＿檢核項目類別
     *
     * @return array
     */
    public function getApiWorkPermitTopicTypeList()
    {
        $ret = array();
        //取第一層
        $data = wp_topic_type::orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
