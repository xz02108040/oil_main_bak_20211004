<?php

namespace App\Http\Traits\WorkPermit;

use App\Model\User;
use App\Model\WorkPermit\wp_check_kind;
use Session;
use App\Model\WorkPermit\wp_work_check;

/**
 * 工作許可證_施工單_許可工作項目
 *
 */
trait WorkPermitWorkOrderCheckTrait
{
    /**
     * 新增 工作許可證_施工單_題目_紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkOrderCheck($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        if(!$data->wp_check_kind_id) return $ret;

        $INS = new wp_work_check();
        $INS->wp_work_id           = $data->wp_work_id;
        $INS->wp_check_kind_id     = $data->wp_check_kind_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 取代原本的 指定專業人員<工地負責人，安衛人員>
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function addWorkPermitWorkOrderCheck($wid,$kind_id,$mod_user = 1)
    {
        if(!$kind_id) return false;
        //1. 先檢查是否跟原本紀錄一至
        $isExist = wp_work_check::isExist($wid,$kind_id);
        if($isExist) return 0;

        //2. 先作廢原本的
        $tmp = [];
        $tmp['isClose'] = 'Y';
        $this->setWorkPermitWorkOrderCheck($isExist,($tmp),$mod_user);

        //3. 新增
        $tmp = [];
        $tmp['wp_work_id']         = $wid;
        $tmp['wp_check_kind_id']   = $kind_id;
        return $this->createWorkPermitWorkOrderCheck($tmp,$mod_user);
    }

    /**
     * 關閉 原本的 工作項目
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function closeWorkPermitWorkOrderCheck($wid,$item_id,$mod_user = 1)
    {
        $now     = date('Y-m-d H:i:s');

        //作廢原本的
        $UPD = wp_work_check::where('wp_work_id',$wid);
        $UPD = $UPD->where('wp_check_kind_id',$item_id);
        return $UPD->update(['isClose'=>'Y','close_user'=>$mod_user,'mod_user'=>$mod_user,'close_stamp'=>$now]);
    }

    /**
     * 修改 工作許可證_施工單_題目_紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkOrderCheck($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_check::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //名稱
        if(isset($data->wp_check_kind_id) && $data->wp_check_kind_id && $data->wp_check_kind_id !== $UPD->wp_check_kind_id)
        {
            $isUp++;
            $UPD->wp_check_kind_id = $data->wp_check_kind_id;
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
     * 取得 工作許可證_施工單_題目_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitWorkOrderCheckList($wid,$isApp = 0)
    {
        $ret = array();
        //取第一層
        $data = wp_work_check::join('wp_check_kind as k','k.id','=','wp_work_check.wp_check_kind_id')->
        where('wp_work_check.wp_work_id',$wid)->where('wp_work_check.isClose','N')->
        select('wp_work_check.*','k.name');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                if($isApp)
                {
                    $tmp = [];
                    $tmp['id']    = $v->wp_check_kind_id;
                    $tmp['name']  = $v->name;
                    $ret[] = $tmp;
                } else {
                    $data[$k]['close_user']     = User::getName($v['close_user']);
                    $data[$k]['new_user']       = User::getName($v['new_user']);
                    $data[$k]['mod_user']       = User::getName($v['mod_user']);
                }
            }
            if(!$isApp)$ret = (object)$data;
        }

        return $ret;
    }

}
