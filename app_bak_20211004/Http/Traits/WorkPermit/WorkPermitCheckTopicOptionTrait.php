<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Engineering\e_project_license;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證_施工單_檢點單_選項紀錄
 *
 */
trait WorkPermitCheckTopicOptionTrait
{
    /**
     * 新增 工作許可證_施工單_檢點單_選項紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitCheckTopicOption($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_check_topic_a();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_list_id       = $data->wp_work_list_id;
        $INS->wp_check_id           = $data->wp_check_id;
        $INS->wp_check_topic_id     = $data->wp_check_topic_id;
        $INS->wp_check_topic_a_id   = $data->wp_check_topic_a_id;
        $INS->wp_work_check_topic_id= $data->wp_work_check_topic_id;
        $INS->ans_value             = $data->ans_value;
        $INS->isImg                 = isset($data->isImg)? $data->isImg : 'N';
        $INS->isGPS                 = isset($data->isGPS)? $data->isGPS : 'N';
        $INS->GPSX                  = isset($data->GPSX)? $data->GPSX : 0;
        $INS->GPSY                  = isset($data->GPSY)? $data->GPSY : 0;
        $INS->wp_work_img_id        = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_施工單_檢點單_選項紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitCheckTopicOption($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_check_topic_a::find($id);
        if(!isset($UPD->ans_value)) return $ret;
        //答案
        if(isset($data->ans_value) && $data->ans_value && $data->ans_value !== $UPD->ans_value)
        {
            $isUp++;
            $UPD->ans_value         = $data->ans_value;
            $UPD->isImg             = isset($data->isImg)? $data->isImg : 'N';
            $UPD->isGPS             = isset($data->isGPS)? $data->isGPS : 'N';
            $UPD->GPSX              = isset($data->GPSX)? $data->GPSX : 0;
            $UPD->GPSY              = isset($data->GPSY)? $data->GPSY : 0;
            $UPD->wp_work_img_id    = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;
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
     * 取得 工作許可證_施工單_檢點單_選項紀錄
     *
     * @return array
     */
    public function getApiWorkPermitCheckTopicOptionList($work_topic_id )
    {
        $ret = array();
        //取第一層
        $data = wp_work_check_topic_a::where('wp_work_topic_id',$work_topic_id)->
                where('isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_施工單_檢點單_選項紀錄
     *
     * @return array
     */
    public function getApiWorkPermitCheckTopicOption($work_id,$check_id,$work_check_topic_id = 0,$appkey = '')
    {
        $ret = array();
        //取第一層
        $data = wp_work_check_topic_a::
        join('wp_check_topic_a as cta','cta.id','=','wp_work_check_topic_a.wp_check_topic_a_id')->
        where('wp_work_check_topic_a.wp_work_id',$work_id)->where('wp_work_check_topic_a.wp_check_id',$check_id)->
        where('wp_work_check_topic_a.isClose','N')->select('wp_work_check_topic_a.*','cta.wp_option_type','cta.engineering_identity_id');
        if($check_id <= 5 && $work_check_topic_id)
        {
            $data = $data->where('wp_work_check_topic_a.wp_work_check_topic_id',$work_check_topic_id);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $wp_option_type = $v->wp_option_type;
                //IMG 圖片
                if($v->wp_work_img_id)
                {
                    $ans_val = url('img/Permit').'/'.SHCSLib::encode($v->wp_work_img_id).'?key='.$appkey;
                }
                //專業人員
                elseif($wp_option_type == 10)
                {
                    $ans_val = User::getName($v->ans_value);
                }
                //證號
                elseif($wp_option_type == 12)
                {
                    $project_id = wp_work::getProjectId($work_id);
                    $ans_val = e_project_license::getUserIdentityLicenseCode($project_id,$v->ans_value,$v->engineering_identity_id);
                }
                else {
                    $unit = (strlen($v->ans_value))? wp_check_topic_a::getUnit($v->wp_check_topic_a_id) : '';
                    $ans_val = $v->ans_value . $unit;
                }
                $ret[$v->wp_check_topic_a_id] = $ans_val;
            }
        }

        return $ret;
    }

    public function uploadWorkPermitTopicCheckImgRecord($work_id,$list_id,$work_process_id,$topic_a_id,$check_topic_a_id,$ans_val,$mod_user)
    {
        list($topic_id,$option_type) = wp_permit_topic_a::getTopicIdList($topic_a_id);
        $wp_work_check_topic_id      = wp_work_check_topic::isExist($work_id,$list_id,$work_process_id);
        if(!$wp_work_check_topic_id) return false;
        $wp_work_check_topic_a_id    = wp_work_check_topic_a::isExist($work_id,$wp_work_check_topic_id,$check_topic_a_id);
//        dd($wp_work_check_topic_id,$wp_work_check_topic_a_id);
        if(!$wp_work_check_topic_a_id) return false;
        //新增填寫紀錄
        $INS = [];
        $INS['ans_value']               = '';
        $INS['wp_work_img_id']          = 0;
        $INS['isImg']                   = 'Y';
        //產生圖片記錄
        if(strlen($ans_val) > 10)
        {
            $INS['wp_work_id']              = $work_id;
            $INS['wp_work_list_id']         = $list_id;
            $INS['wp_work_process_id']      = $work_process_id;
            $INS['wp_permit_topic_id']      = $topic_id;
            $INS['wp_permit_topic_a_id']    = $topic_a_id;

            $filepath = config('mycfg.permit_check_path').date('Y/m/').$work_id.'/';
            $filename = 'check_'.$check_topic_a_id.'_'.time().'.jpg';
            $wp_work_img_id = $this->createWorkPermitWorkImg($INS,$filepath,$filename,$ans_val,2,$mod_user);

            //圖片路徑
            $INS['wp_work_img_id']  = $wp_work_img_id;
            $INS['ans_value']       = ($wp_work_img_id)? $filepath.$filename : 0;
        }
//        dd($wp_work_check_topic_a_id,$ans_val,$INS);
        $ret = $this->setWorkPermitCheckTopicOption($wp_work_check_topic_a_id,$INS,$mod_user);
        if($ret)
        {
            $process_id = wp_work_process::getProcess($work_process_id);
            $lostImgAry = $this->getLostImgTopic($work_id,$process_id);
            $UPD        = wp_work_process::find($work_process_id);
            $UPD->lost_img_amt  = count($lostImgAry);
            $UPD->mod_user      = $mod_user;
            $UPD->save();
        }
        return $ret;
    }

    /**
     * 取得 工作許可證 對應工程身份之 <已在廠>承攬商成員
     *
     * @return array
     */
    public function getApiWorkPermitCheckTopicOptionIdentity($wid,$caid ,$identity_id = 0)
    {
        $ret = '';
        if(!$wid || !$caid) return $ret;
        //取第一層
        $identity_id = $identity_id ? $identity_id : wp_check_topic_a::getIdentity($caid);
        if($identity_id)
        {
            if(in_array($identity_id,[1,2]))
            {
                $data = wp_work_worker::getRootMen($wid,$identity_id,0,1);
            } else {
                $data = wp_work_worker::getLockMenSelect($wid,$identity_id,0,1);
            }

//            dd([$wid,$taid,$identity_id,$ret]);

            if(count($data))
            {
                $str = '';
                foreach ($data as $val)
                {
                    if($str) $str .= '，';
                    $str .= isset($val['name'])? $val['name'] : '';
                }
                $ret = $str;
            }
        }

        return $ret;
    }

}
