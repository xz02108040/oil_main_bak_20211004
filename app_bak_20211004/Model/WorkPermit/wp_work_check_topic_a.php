<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\sys_param;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_check_topic_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_check_topic_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($work_id,$wp_work_check_topic_id,$check_topic_a_id)
    {
        if(!$work_id || !$wp_work_check_topic_id || !$check_topic_a_id) return 0;
        $data = wp_work_check_topic_a::where('wp_work_id',$work_id)->where('wp_work_check_topic_id',$wp_work_check_topic_id)->
        where('wp_check_topic_a_id',$check_topic_a_id)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }


    //取得 下拉選擇全部
    protected  function getCheckDetailTopicAns($wp_work_check_topic_id, $img_resize = 0)
    {
        $ret    = [];
        $data   = wp_work_check_topic_a::where('wp_work_check_topic_id',$wp_work_check_topic_id)->where('isClose','N');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                if($val->isImg == 'Y')
                {
                    $resize = ($img_resize)? '?size='.$img_resize : '';
//                    $img = ($val->wp_work_img_id)? (url('img/Permit').'/'.SHCSLib::encode($val->wp_work_img_id).$resize) : '';
                    $img = ($val->wp_work_img_id)? SHCSLib::toImgBase64String('permit',$val->wp_work_img_id,$img_resize) : '';
                    $ret[$val->wp_check_topic_a_id] = $img;
                } else {
                    $ret[$val->wp_check_topic_a_id] = $val->ans_value;
                }
            }
        }


        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getData($topic_id, $img_resize = 0)
    {
        $ret    = [];
        $resize = ($img_resize)? '?size='.$img_resize : '';
        $data   = wp_work_check_topic_a::
        join('wp_check_topic_a as c','c.id','=','wp_work_check_topic_a.wp_check_topic_a_id')->
        where('wp_work_check_topic_id',$topic_id)->where('wp_work_check_topic_a.isClose','N')->
        select('wp_work_check_topic_a.*','c.wp_option_type')->get();

        foreach ($data as $key => $val)
        {
            $ans = $val->ans_value;
            if(in_array($val->wp_option_type,[6,7]) && $val->wp_work_img_id)
            {
                //$ans = url('img/Permit').'/'.SHCSLib::encode($val->wp_work_img_id).$resize;
                $ans = SHCSLib::toImgBase64String('permit',$val->wp_work_img_id,$img_resize) ;
            }
            $ret[$val->wp_check_topic_a_id] = $ans;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getRecordData($topic_id,$recordData = [0,'',''])
    {
        $ret    = [];
        list($record_user,$record_stamp,$wp_work_process_id) = $recordData;
        $data   = wp_work_check_topic_a::where('wp_work_check_topic_id',$topic_id)->where('wp_work_check_topic_a.isClose','N')->
        select('wp_work_check_topic_a.*');

        if($data->count())
        {
            $data = $data->get();
            $record1    = sys_param::getParam('PERMIT_CHECK_RECORD1_ID');
            $record1Ary = explode(',',$record1);
            $record2    = sys_param::getParam('PERMIT_CHECK_RECORD2_ID');
            $record2Ary = explode(',',$record2);
            $record3    = sys_param::getParam('PERMIT_CHECK_RECORD3_ID');
            $record3Ary = explode(',',$record3);
            $record4    = sys_param::getParam('PERMIT_CHECK_RECORD4_ID');
            $record4Ary = explode(',',$record4);
            $record5    = sys_param::getParam('PERMIT_CHECK_RECORD5_ID');
            $record5Ary = explode(',',$record5);
            $record5n       = sys_param::getParam('PERMIT_CHECK_RECORD5_NAME');
            $record5nAry    = explode(',',$record5n);
            $recordImg      = sys_param::getParam('PERMIT_CHECK_RECORD1_PHOTO');
            $recordImgAry   = explode(',',$recordImg);

            foreach ($data as $key => $val)
            {
                $wp_work_id                 = $val->wp_work_id;
                $wp_work_list_id            = $val->wp_work_list_id;
                $wp_check_id                = $val->wp_check_id;
                $check_topic_a_id           = $val->wp_check_topic_a_id;
                $wp_work_check_topic_a_id   = $val->id;
                $ans                        = $val->ans_value;
                $wp_work_img_id             = $val->wp_work_img_id;

                $ret['wp_work_id']                  = $wp_work_id;
                $ret['wp_work_list_id']             = $wp_work_list_id;
                $ret['wp_work_process_id']          = $wp_work_process_id;
                $ret['wp_check_id']                 = $wp_check_id;
                $ret['record_user']                 = $record_user;
                $ret['mod_user']                    = $record_user;
                $ret['record_stamp']                = $record_stamp;
                if(in_array($check_topic_a_id,$record1Ary))
                {
                    $ret['wp_check_topic_a_id1']        = $check_topic_a_id;
                    $ret['wp_work_check_topic_a_id1']    = $wp_work_check_topic_a_id;
                    $ret['record1'] = $ans;
                    $ret['isOver1'] = SHCSLib::isPermitCheckOverLimit('record1', $ans);
                }
                if(in_array($check_topic_a_id,$record2Ary))
                {
                    $ret['wp_check_topic_a_id2']        = $check_topic_a_id;
                    $ret['wp_work_check_topic_a_id2']    = $wp_work_check_topic_a_id;
                    $ret['record2'] = $ans;
                    $ret['isOver2'] = SHCSLib::isPermitCheckOverLimit('record2', $ans);
                }
                if(in_array($check_topic_a_id,$record3Ary))
                {
                    $ret['wp_check_topic_a_id3']        = $check_topic_a_id;
                    $ret['wp_work_check_topic_a_id3']    = $wp_work_check_topic_a_id;
                    $ret['record3'] = $ans;
                    $ret['isOver3'] = SHCSLib::isPermitCheckOverLimit('record3', $ans);
                }
                if(in_array($check_topic_a_id,$record4Ary))
                {
                    $ret['wp_check_topic_a_id4']        = $check_topic_a_id;
                    $ret['wp_work_check_topic_a_id4']    = $wp_work_check_topic_a_id;
                    $ret['record4'] = $ans;
                    $ret['isOver4'] = SHCSLib::isPermitCheckOverLimit('record4', $ans);
                }
                if(in_array($check_topic_a_id,$record5Ary))
                {
                    $ret['wp_check_topic_a_id5']        = $check_topic_a_id;
                    $ret['wp_work_check_topic_a_id5']    = $wp_work_check_topic_a_id;
                    $ret['record5'] = $ans;
                    $ret['isOver5'] = 'N';
                }
                if(in_array($check_topic_a_id,$record5nAry))
                {
                    $ret['record5n'] = $ans;
                }
                if(in_array($check_topic_a_id,$recordImgAry))
                {
                    $ret['wp_work_img_id'] = $wp_work_img_id;
                }
            }
        }


        return $ret;
    }

    //工作許可證_題目_選項填寫結果_圖片
    protected  function getCheckDetailTopicAns_Img($wp_work_check_topic_id, $img_resize = 0)
    {
        $img    = '';
        $data   = wp_work_check_topic_a::where('wp_work_check_topic_id', $wp_work_check_topic_id)->where('isClose', 'N')->where('isImg', 'Y');
        if ($data->count()) {
            $data = $data->get();
            foreach ($data as $key => $val) {
                $resize = ($img_resize) ? '?size=' . $img_resize : '';
                $img = ($val->wp_work_img_id) ? SHCSLib::toImgBase64String('permit', $val->wp_work_img_id, $img_resize) : '';
            }
        }

        return $img;
    }
}
