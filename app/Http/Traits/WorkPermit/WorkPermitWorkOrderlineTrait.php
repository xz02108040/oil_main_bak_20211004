<?php

namespace App\Http\Traits\WorkPermit;

use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_permit_workitem_a;
use App\Model\WorkPermit\wp_work_line;
use App\Model\WorkPermit\wp_work_topic;
use Session;
use App\Model\WorkPermit\wp_work_workitem;

/**
 * 工作許可證_施工單_許可工作項目
 *
 */
trait WorkPermitWorkOrderlineTrait
{
    /**
     * 新增 工作許可證_施工單_題目_紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkOrderLine($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_line();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_permit_pipeline_id = $data->wp_permit_pipeline_id;
        $INS->memo                  = $data->memo;

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
    public function addWorkPermitWorkOrderLine($wid,$line_id,$other_memo = '',$mod_user = 1)
    {
        if(!$line_id) return false;
        //1. 先檢查是否跟原本紀錄一至
        $isExist = wp_work_line::isExist($wid,$line_id);
        if($isExist) return 0;

        //2. 先作廢原本的
        $tmp = [];
        $tmp['isClose'] = 'Y';
        $this->setWorkPermitWorkOrderLine($isExist,($tmp),$mod_user);

        //3. 新增
        $tmp = [];
        $tmp['wp_work_id']              = $wid;
        $tmp['wp_permit_pipeline_id']   = $line_id;
        $tmp['memo']                    = $other_memo;
        return $this->createWorkPermitWorkOrderLine($tmp,$mod_user);
    }

    /**
     * 關閉 原本的 工作項目
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function closeWorkPermitWorkOrderLine($wid,$line_id,$mod_user = 1)
    {
        $now     = date('Y-m-d H:i:s');

        //作廢原本的
        $UPD = wp_work_line::where('wp_work_id',$wid);
        $UPD = $UPD->where('wp_permit_pipeline_id',$line_id);
        return $UPD->update(['isClose'=>'Y','close_user'=>$mod_user,'mod_user'=>$mod_user,'close_stamp'=>$now]);
    }

    /**
     * 修改 工作許可證_施工單_題目_紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkOrderLine($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_line::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //名稱
        if(isset($data->wp_permit_pipeline_id) && $data->wp_permit_pipeline_id && $data->wp_permit_pipeline_id !== $UPD->wp_permit_pipeline_id)
        {
            $isUp++;
            $UPD->wp_permit_pipeline_id = $data->wp_permit_pipeline_id;
        }
        //名稱
        if(isset($data->memo) && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
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
    public function getApiWorkPermitWorkOrderLineList($wid, $isApp = 0)
    {
        $ret = array();
        //取第一層
        $data = wp_work_line::join('wp_permit_pipeline as l','l.id','=','wp_work_line.wp_permit_pipeline_id')->
        where('wp_work_line.wp_work_id',$wid)->where('wp_work_line.isClose','N')->
        select('wp_work_line.*','l.name');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                if($isApp)
                {
                    $tmp = [];
                    $tmp['id']    = $v->wp_permit_pipeline_id;
                    $tmp['name']  = $v->name;
                    $tmp['memo']  = $v->memo;
                    $ret[] = $tmp;
                } else {
                    $data[$k]['close_user']     = User::getName($v->close_user);
                    $data[$k]['new_user']       = User::getName($v->new_user);
                    $data[$k]['mod_user']       = User::getName($v->mod_user);
                }
            }
            if(!$isApp)$ret = (object)$data;
        }

        return $ret;
    }
}
