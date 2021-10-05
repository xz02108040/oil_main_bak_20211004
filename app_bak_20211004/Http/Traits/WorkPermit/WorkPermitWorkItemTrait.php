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

/**
 * 工作許可證 工作項目
 *
 */
trait WorkPermitWorkItemTrait
{
    /**
     * 新增 工作許可證工作項目
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkItem($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_permit_workitem();
        $INS->wp_permit_kind_id     = $data->wp_permit_kind_id;
        $INS->name                  = $data->name;
        $INS->show_order            = is_numeric($data->show_order)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證工作項目
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkItem($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_workitem::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //種類
        if(isset($data->wp_permit_kind_id) && is_numeric($data->wp_permit_kind_id) && $data->wp_permit_kind_id !== $UPD->wp_permit_kind_id)
        {
            $isUp++;
            $UPD->wp_permit_kind_id = $data->wp_permit_kind_id;
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
     * 取得 工作許可證工作項目
     *
     * @return array
     */
    public function getApiWorkPermitWorkItemList($kid)
    {
        $ret = array();
        //取第一層
        $data = wp_permit_workitem::join('wp_permit_kind as k','k.id','=','wp_permit_workitem.wp_permit_kind_id')->
                where('k.id',$kid)->
                select('wp_permit_workitem.*','k.name as kind');//->where('isClose','N')

        $data = $data->orderby('wp_permit_workitem.isClose')->orderby('wp_permit_workitem.show_order')->get();
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
    public function getApiWorkPermitWorkItem($kid = 0)
    {
        $ret = array();
        $data = wp_permit_workitem::where('isClose','N')->orderby('show_order');
        if($kid)
        {
            $data = $data->where('wp_permit_kind_id',$kid);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']      = $v->id;
                $tmp['name']    = $v->name;
                $tmp['isText']  = $v->isText;
                $tmp['wp_check']   = $this->getApiWorkPermitWorkItemCheck($v->id);
                $tmp['wp_danger']  = $this->getApiWorkPermitWorkItemDanger($v->id);
                $ret[]   = $tmp;
            }
        }
        return $ret;
    }

}
