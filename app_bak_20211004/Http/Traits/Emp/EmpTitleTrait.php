<?php

namespace App\Http\Traits\Emp;

use App\Lib\SHCSLib;
use App\Model\Emp\be_title;
use App\Model\User;

/**
 * 組織職稱
 *
 */
trait EmpTitleTrait
{
    /**
     * 新增 組織職稱
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBeTitle($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new be_title();
        $INS->name         = $data->name;
        $INS->show_order   = is_numeric($data->show_order)? $data->show_order : 999;
        $INS->memo         = ($data->memo)? $data->memo : '';
        $INS->isAd         = ($data->isAd == 'Y')? 'Y' : 'N';
        $INS->new_user     = $mod_user;
        $INS->mod_user     = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 組織職稱
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBeTitle($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = be_title::find($id);
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
        //主管職
        if(isset($data->memo) && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //主管職
        if(isset($data->isAd) && ($data->isAd) && $data->isAd !== $UPD->isAd)
        {
            $isUp++;
            $UPD->isAd = $data->isAd;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = $data->isClose;
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
     * 取得 組織職稱
     *
     * @return array
     */
    public function getApiBeTitleList()
    {
        $ret = array();
        //取第一層
        $data = be_title::orderBy('isClose')->orderBy('show_order')->get();

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
