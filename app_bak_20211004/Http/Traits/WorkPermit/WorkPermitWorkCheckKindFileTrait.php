<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\WorkPermit\wp_check_kind_f;
use http\Url;
use Storage;
use App\Model\WorkPermit\wp_work_check_kind_file;

/**
 * 工單_施工單_照片紀錄<簽名＆拍照>
 *
 */
trait WorkPermitWorkCheckKindFileTrait
{
    /**
     * 新增 工單_施工單_照片紀錄<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitCheckKindFile($data,$mod_user = 1)
    {
        $ret = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id) || !isset($data->wp_check_kind_id) || !isset($data->filepath)) return $ret;
        if(Storage::put($data->filepath,$data->file))
        {
            $INS = new wp_work_check_kind_file();
            $INS->wp_work_id            = $data->wp_work_id;
            $INS->wp_check_kind_id      = $data->wp_check_kind_id;
            $INS->wp_check_kind_f_id    = $data->wp_check_kind_f_id;
            $INS->file_path             = $data->filepath;

            $INS->new_user      = $mod_user;
            $INS->mod_user      = $mod_user;
            $ret = ($INS->save())? $INS->id : 0;
        }
        return $ret;
    }
    /**
     * 更新 工單_施工單_照片紀錄<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkPermitCheckKindFile($data,$mod_user = 1)
    {
        $ret = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id) || !isset($data->id) || !isset($data->filepath)) return $ret;
        //存儲
        if(Storage::put($data->filepath,$data->file))
        {
            //作廢原本
            $UPD = [];
            $UPD['isClose'] = 'Y';
            if($this->setWorkPermitCheckKindFile($data->id,$UPD,$mod_user))
            {
                //再新增一筆
                $INS = new wp_work_check_kind_file();
                $INS->wp_work_id            = $data->wp_work_id;
                $INS->wp_check_kind_id      = $data->wp_check_kind_id;
                $INS->wp_check_kind_f_id    = $data->wp_check_kind_f_id;
                $INS->file_path             = $data->filepath;

                $INS->new_user      = $mod_user;
                $INS->mod_user      = $mod_user;
                $ret = ($INS->save())? $INS->id : 0;
            }
        }
        return $ret;
    }
    /**
     * 新增 工單_施工單_照片紀錄<簽名＆拍照>
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitCheckKindFile($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        $isUp = 0;
        $now  = date('Y-m-d H:i:s');

        $UPD = wp_work_check_kind_file::find($id);
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
    public function getApiWorkPermitWorkCheckKindFileList($wid,$kind_id = 0)
    {
        $ret = array();
        $itemAry = wp_check_kind_f::getSelect(0);
        //取第一層
        $data = wp_work_check_kind_file::where('wp_work_id',$wid)->where('isClose','N');
        if($kind_id)
        {
            $data = $data->where('wp_check_kind_id',$kind_id);
        }
        if($data->count())
        {
            $data = $data->orderby('id','desc')->get();
            foreach ($data as $k => $v)
            {
                $name           = isset($itemAry[$v->wp_check_kind_f_id])? $itemAry[$v->wp_check_kind_f_id] : '';

                $tmp = [];
                $tmp['id']                  = $v->id;
                $tmp['wp_check_kind_id']    = $v->wp_check_kind_id;
                $tmp['wp_check_kind_f_id']  = $v->wp_check_kind_f_id;
                $tmp['name']                = $name;
                $tmp['url']                 = SHCSLib::url('file/','A'.$v->id,'sid=WorkCheckFile');
                $ret[] = $tmp;
            }
        }
        return $ret;
    }
}
