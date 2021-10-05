<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_work_check_kind;
use App\Model\WorkPermit\wp_work_img;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工單_施工單_危險作業
 *
 */
trait WorkPermitWorkCheckKindTrait
{
    /**
     * 新增 工單_施工單_危險作業<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkCheckKind($data,$mod_user = 1)
    {
        $ret = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_check_kind();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_check_kind_id      = $data->wp_check_kind_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        return $ret;
    }
    /**
     * 新增 工單_施工單_危險作業<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkCheckKind($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        $isUp = 0;
        $now = date('Y-m-d H:i:s');

        $UPD = wp_work_check_kind::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;

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
     * 取得 工單_施工單_危險作業_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitWorkCheckKindList($wid,$isApp = 0)
    {
        $ret = array();
        $itemAry = wp_check_kind::getSelect(0);
        //取第一層
        $data = wp_work_check_kind::where('wp_work_id',$wid)->where('isClose','N');
        if($data->count())
        {
            $data = $data->orderby('id','desc')->get();
            foreach ($data as $k => $v)
            {
                $name           = isset($itemAry[$v->wp_check_kind_id])? $itemAry[$v->wp_check_kind_id] : '';
                if($isApp)
                {
                    $tmp = [];
                    $tmp['id']    = $v->id;
                    $tmp['name']  = $name;
                    $ret[] = $tmp;
                } else {
                    $data[$k]['check_kind_id']           = $v->wp_check_kind_id;
                    $data[$k]['name']           = $name;
                    $data[$k]['topic']          = $this->getApiWorkPermitCheckTopicRecord($wid,$v->wp_check_kind_id);
                    $data[$k]['file']           = $this->getApiWorkPermitWorkCheckKindFileList($wid,$v->wp_check_kind_id);
//                    $data[$k]['close_user']     = User::getName($v->close_user);
//                    $data[$k]['new_user']       = User::getName($v->new_user);
//                    $data[$k]['mod_user']       = User::getName($v->mod_user);
                }

            }
            if(!$isApp)$ret = (object)$data;
        }

        return $ret;
    }
}
