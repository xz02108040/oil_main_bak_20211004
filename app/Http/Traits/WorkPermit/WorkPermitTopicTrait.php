<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_topic_type;
use App\Model\WorkPermit\wp_work_topic;

/**
 * 工作許可證_題目
 *
 */
trait WorkPermitTopicTrait
{
    /**
     * 新增 工作許可證_題目
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitTopic($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_permit_topic();
        $INS->name              = $data->name;
        $INS->wp_topic_type     = $data->wp_topic_type;
        $INS->wp_permit_id      = $data->wp_permit_id;
        $INS->bc_type_app       = isset($data->bc_type_app)? $data->bc_type_app : 0;
        $INS->wp_check_kind_id  = isset($data->wp_check_kind_id)? $data->wp_check_kind_id : 0;
        $INS->show_order        = ($data->show_order > 0)? $data->show_order : 999;
        $INS->isCheck           = (in_array($data->isCheck,['Y','N']))? $data->isCheck : 'N';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_題目
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitTopic($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = wp_permit_topic::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //種類
        if(isset($data->wp_topic_type) && $data->wp_topic_type > 0 && $data->wp_topic_type !== $UPD->wp_topic_type)
        {
            $isUp++;
            $UPD->wp_topic_type = $data->wp_topic_type;
        }
        //帳號身分
        if(isset($data->bc_type_app) && $data->bc_type_app !== $UPD->bc_type_app)
        {
            $isUp++;
            $UPD->bc_type_app = $data->bc_type_app;
        }
        //附加檢點單
        if(isset($data->wp_check_kind_id) && $data->wp_check_kind_id !== $UPD->wp_check_kind_id)
        {
            $isUp++;
            $UPD->wp_check_kind_id = $data->wp_check_kind_id;
        }
        //排序
        if(isset($data->show_order) && $data->show_order > 0 && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //顯示勾選項目
        if(isset($data->isCheck) && in_array($data->isCheck,['Y','N']) && $data->isCheck !== $UPD->isCheck)
        {
            $isUp++;
            $UPD->isCheck    = $data->isCheck;
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
     * 取得 工作許可證_題目
     *
     * @return array
     */
    public function getApiWorkPermitTopicList($pid)
    {
        $ret = array();
        $bctypeAry  = bc_type_app::getSelect(0,0);
        $kindAry    = wp_check_kind::getSelect(0);
        //取第一層
        $data = wp_permit_topic::
        join('wp_permit as p','p.id','=','wp_permit_topic.wp_permit_id')->
        join('wp_topic_type as t','t.id','=','wp_permit_topic.wp_topic_type')->
            select('wp_permit_topic.*','p.name as wp_permit','t.name as type','t.isOption')->
        where('wp_permit_id',$pid)->orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['bc_type']        = isset($bctypeAry[$v->bc_type_app])? $bctypeAry[$v->bc_type_app] : '';
                $data[$k]['kind']           = isset($kindAry[$v->wp_check_kind_id])? $kindAry[$v->wp_check_kind_id] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
