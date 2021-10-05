<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_check_topic;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_topic_type;

/**
 * 檢點單_檢核項目
 *
 */
trait WorkCheckTopicTrait
{
    /**
     * 新增 檢點單_檢核項目
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkCheckTopic($data,$copy_id = 0,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
//        dd([$data,$copy_id]);
        $INS = new wp_check_topic();
        $INS->name              = $data->name;
        $INS->wp_check_id       = $data->wp_check_id;
        $INS->wp_topic_type     = $data->wp_topic_type;
        $INS->circle            = ($data->circle > 0)? $data->circle : 0;
        $INS->show_order        = ($data->show_order > 0)? $data->show_order : 999;
        $INS->isCheck           = (in_array($data->isCheck,['Y','N']))? $data->isCheck : 'N';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret && $copy_id)
        {
            $topicA = wp_check_topic_a::where('wp_check_topic_id',$copy_id)->where('isClose','N')->
                    orderby('show_order');
            if($topicA->count())
            {
                $topicA = $topicA->get();
                foreach ($topicA as $val)
                {
                    $tmp = [];
                    $tmp['wp_check_id']         = $data->wp_check_id;
                    $tmp['wp_check_topic_id']   = $ret;
                    $tmp['wp_option_type']      = $val->wp_option_type;
                    $tmp['name']                = $val->name;
                    $tmp['memo']                = $val->memo;
                    $tmp['unit']                = $val->unit;
                    $tmp['safe_val']            = $val->safe_val;
                    $tmp['show_order']          = $val->show_order;
                    $this->createWorkCheckTopicOption($tmp,$mod_user);
                }
            }
        }

        return $ret;
    }

    /**
     * 修改 檢點單_檢核項目
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkCheckTopic($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_check_topic::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //種類
        if(isset($data->wp_topic_type) && $data->wp_topic_type > 0 && $data->wp_topic_type !== $UPD->wp_topic_type)
        {
            $isUp++;
            $UPD->wp_topic_type = $data->wp_topic_type;
        }
        //紀錄週期
        if(isset($data->circle) && $data->circle >= 0 && $data->circle !== $UPD->circle)
        {
            $isUp++;
            $UPD->circle = $data->circle;
        }
        //排序
        if(isset($data->show_order) && $data->show_order > 0 && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //顯示勾選項目
        if(isset($data->isCheck) && in_array($data->isCheck,['Y','N']) && $data->isCheck !== $UPD->isCheck)
        {
            $isUp++;
            $UPD->isCheck    = $data->isCheck;
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
     * 取得 檢點單_檢核項目
     *
     * @return array
     */
    public function getApiWorkCheckTopicList($id)
    {
        $ret = array();
        //取第一層
        $data = wp_check_topic::join('wp_topic_type as t','t.id','=','wp_check_topic.wp_topic_type')->
                join('wp_check as c','c.id','=','wp_check_topic.wp_check_id')->
                where('wp_check_topic.wp_check_id',$id)->
                select('wp_check_topic.*','c.name as wp_check','t.name as type','t.isOption')->
                orderby('wp_check_topic.isClose')->orderby('wp_check_topic.show_order')->get();

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
     * 取得 檢點單_檢核項目
     *
     * @return array
     */
    public function getApiWorkCheckTopic($id,$work_id = 0, $topic_id = 0)
    {
        $ret = array();
        //取第一層
        $data = wp_check_topic::where('wp_check_id',$id)->where('isClose','N');
        if($topic_id)
        {
            $data = $data->where('wp_check_topic_id',$topic_id);
        }
        $data = $data->orderby('show_order')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['check_topic_id']  = $v->id;
                $tmp['name']            = $v->name;
                $tmp['circle']          = $v->circle;
                $tmp['isCheck']         = $v->isCheck;
                $tmp['option']          = $this->getApiWorkCheckTopicOption($v->id,$work_id);
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
