<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_permit_workitem_b;

/**
 * 工作許可證對應之 附加檢點單
 *
 */
trait WorkPermitWorkItemCheckTrait
{
    /**
     * 新增 工作許可證危險等級
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkItemCheck($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_workitem_id)) return $ret;

        $INS = new wp_permit_workitem_b();
        $INS->wp_permit_kind_id     = wp_permit_workitem::getKind($data->wp_permit_workitem_id);
        $INS->wp_permit_workitem_id = $data->wp_permit_workitem_id;
        $INS->wp_check_kind_id      = $data->wp_check_kind_id;
        $INS->show_order            = is_numeric($data->show_order)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證危險等級
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkItemCheck($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_workitem_b::find($id);
        if(!isset($UPD->wp_permit_workitem_id)) return $ret;
        //內容
        if(isset($data->wp_check_kind_id) && $data->wp_check_kind_id && $data->wp_check_kind_id !== $UPD->wp_check_kind_id)
        {
            $isUp++;
            $UPD->wp_check_kind_id = $data->wp_check_kind_id;
        }
        //種類
        if(isset($data->wp_permit_workitem_id) && is_numeric($data->wp_permit_workitem_id) && $data->wp_permit_workitem_id !== $UPD->wp_permit_workitem_id)
        {
            $isUp++;
            $UPD->wp_permit_kind_id     = wp_permit_workitem::getKind($data->wp_permit_workitem_id);
            $UPD->wp_permit_workitem_id = $data->wp_permit_workitem_id;
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
     * 取得 工作許可證危險等級
     *
     * @return array
     */
    public function getApiWorkPermitWorkItemCheckList($kid)
    {
        $ret = array();
        //取第一層
        $data = wp_permit_workitem_b::join('wp_permit_kind as k','k.id','=','wp_permit_workitem_b.wp_permit_kind_id')->
                join('wp_permit_workitem as t','t.id','=','wp_permit_workitem_b.wp_permit_workitem_id')->
                join('wp_check_kind as c','c.id','=','wp_permit_workitem_b.wp_check_kind_id')->
                where('wp_permit_workitem_id',$kid)->
                select('wp_permit_workitem_b.*','k.name as kind','t.name as workitem','c.name as check_name');//->where('isClose','N')

        $data = $data->orderby('wp_permit_workitem_b.isClose')->orderby('wp_permit_workitem_b.show_order')->get();
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

    /**
     * 取得 工作許可證危險等級[APP]
     *
     * @return array
     */
    public function getApiWorkPermitWorkItemCheck($tid = 0)
    {
        $ret = array();
        $data = wp_permit_workitem_b::where('isClose','N')->orderby('show_order');
        if($tid)
        {
            $data = $data->where('wp_permit_workitem_id',$tid);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']      = $v->wp_check_kind_id;
                $ret[]   = $tmp;
            }
        }
        return $ret;
    }
}
