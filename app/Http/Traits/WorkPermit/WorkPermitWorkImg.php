<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_work_img;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證_施工單_照片紀錄<簽名＆拍照>
 *
 */
trait WorkPermitWorkImg
{
    /**
     * 新增 工作許可證_施工單_照片紀錄<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkImg($data,$filepath,$filename,$img,$isResize = 0,$mod_user = 1)
    {
        $ret = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        if(!strlen($img)) return $ret;
        $reSizeAry = ($isResize)? [1920] : [] ;

        $filename = $data->wp_work_id.'_'.$filename; // 傳入的檔案名稱開頭增加工單 ID
        if(SHCSLib::saveBase64ToImg($filepath,$filename,$img))
        {
            $INS = new wp_work_img();
            $INS->wp_work_id            = $data->wp_work_id;
            $INS->wp_work_list_id       = $data->wp_work_list_id;
            $INS->wp_work_process_id    = $data->wp_work_process_id;
            $INS->img_path              = $filepath.$filename;

            $INS->new_user      = $mod_user;
            $INS->mod_user      = $mod_user;
            $ret = ($INS->save())? $INS->id : 0;
        }
        return $ret;
    }
    /**
     * 新增 工作許可證_施工單_照片紀錄<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkImg($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        $isUp = 0;

        $UPD = wp_work_img::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //填寫紀錄
        if(isset($data->wp_work_process_topic_id) && $data->wp_work_process_topic_id && $data->wp_work_process_topic_id !== $UPD->wp_work_process_topic_id)
        {
            $isUp++;
            $UPD->wp_work_process_topic_id = $data->wp_work_process_topic_id;
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

}
