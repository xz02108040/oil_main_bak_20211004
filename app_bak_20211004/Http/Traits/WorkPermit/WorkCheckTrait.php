<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;

/**
 * 檢點單
 *
 */
trait WorkCheckTrait
{
    /**
     * 新增 檢點單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkCheck($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_check();
        $INS->name              = $data->name;
        $INS->wp_check_kind_id  = $data->wp_check_kind_id;
        $INS->show_order        = ($data->show_order > 0)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 檢點單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkCheck($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_check::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //種類
        if(isset($data->wp_check_kind_id) && $data->wp_check_kind_id > 0 && $data->wp_check_kind_id !== $UPD->wp_check_kind_id)
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
     * 取得 檢點單
     *
     * @return array
     */
    public function getApiWorkCheckList()
    {
        $ret = array();
        //取第一層
        $data = wp_check::join('wp_check_kind as k','k.id','=','wp_check.wp_check_kind_id')->
        orderby('wp_check.isClose')->orderby('wp_check.show_order')->
        select('wp_check.*','k.name as kind')->get();

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
     * 取得 檢點單
     *
     * @return array
     */
    public function getApiWorkCheck($check_id,$work_id = 0,$isApi = 1)
    {
        $ret = array();
        //取第一層
        $data = wp_check:: where('id',$check_id)->orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                if($isApi)
                {
                    $ret = $this->getApiWorkCheckTopic($check_id,$work_id);
                } else {
                    $tmp = [];
                    $tmp['name']    = $v->name;
                    $tmp['topic']   = $this->getApiWorkCheckTopic($check_id,$work_id);
                    $ret = $tmp;
                }
            }
        }

        return $ret;
    }

}
