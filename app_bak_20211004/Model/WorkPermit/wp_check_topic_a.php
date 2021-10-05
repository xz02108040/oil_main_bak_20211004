<?php

namespace App\Model\WorkPermit;

use App\Http\Traits\WorkPermit\WorkPermitWorkImg;
use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use App\Lib\SHCSLib;
use Lang;
use function GuzzleHttp\Psr7\str;

class wp_check_topic_a extends Model
{
    use WorkPermitWorkImg;
    /**
     * 使用者Table:
     */
    protected $table = 'wp_check_topic_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_check_topic_a::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$name,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_check_topic_a::where('wp_check_topic_id',$id)->where('name',$id);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //是否為 圖片格式
    protected  function isImgAns($id)
    {
        if(!$id) return 0;
        $data = wp_check_topic_a::where('id',$id)->select('wp_option_type')->first();
        $option_type = (isset($data->wp_option_type))? $data->wp_option_type : 0;
        return (in_array($option_type,[7]))? 1 : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_check_topic_a::where('id',$id)->select('name')->first();
        return (isset($data->name))? $data->name : '';
    }
    //取得 單位
    protected  function getUnit($id)
    {
        if(!$id) return '';
        $data = wp_check_topic_a::where('id',$id)->select('unit')->first();
        return (isset($data->unit))? $data->unit : '';
    }

    //取得 名稱
    protected  function getIdentity($id)
    {
        if(!$id) return 0;
        $data = wp_check_topic_a::where('id',$id)->select('engineering_identity_id')->first();
        return (isset($data->engineering_identity_id))? $data->engineering_identity_id : 0;
    }
    //取得 單位
    protected  function getLimitRange($check_id)
    {
        $ret = [];
        if(!$check_id) return '';
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

        $data = wp_check_topic_a::where('wp_check_id',$check_id)->where('safe_action','!=','')->where('isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['safe_limit1']  = !is_null($val->safe_limit1)? $val->safe_limit1 : 0;
                $tmp['safe_limit2']  = !is_null($val->safe_limit2)? $val->safe_limit2 : 0;
                $tmp['safe_action']  = $val->safe_action;

                if(in_array($val->id,$record1Ary))
                {
                    $title = 'record1';
                }
                if(in_array($val->id,$record2Ary))
                {
                    $title = 'record2';
                }
                if(in_array($val->id,$record3Ary))
                {
                    $title = 'record3';
                }
                if(in_array($val->id,$record4Ary))
                {
                    $title = 'record4';
                }
                if(in_array($val->id,$record5Ary))
                {
                    $title = 'record5';
                }


                $ret[$title] = $tmp;
            }
        }
        return $ret;
    }

    //取得 名稱
    protected  function genData($id,$INS,$ans_val,$mod_user)
    {
        $ret = [];
        if(!$id) return $ret;
        $data = wp_check_topic_a::find($id);

        if(isset($data->id))
        {
            $isAns = in_array($data->wp_option_type,[1,2,3,4,6,7,8,9,13,17]) ? 'Y' : 'N';
            $isImg = in_array($data->wp_option_type,[6,7]) ? 'Y' : 'N';
            $isGPS = in_array($data->wp_option_type,[8]) ? 'Y' : 'N';
            $img_title = $data->wp_option_type;
            $work_id   = $data->wp_check_id;

            $ret['topic_a_id']              = $data->id;
            $ret['wp_option_type']          = $data->wp_option_type;
            $ret['name']                    = $data->name;
            $ret['memo']                    = is_null($data->memo)? '' : $data->memo;
            $ret['unit']                    = is_null($data->unit)? '' : $data->unit;
            $ret['safe_val']                = $data->safe_val;
            $ret['isAns']                   = $isAns;
            $ret['isImg']                   = $isImg;
            $ret['isGPS']                   = $isGPS;
            $ret['GPSX']                    = '';
            $ret['GPSY']                    = '';
            $ret['wp_work_img_id']          = 0;

            if($isGPS == 'Y' && is_string($ans_val))
            {
                $ret['ans_value'] = $ans_val;
                //切割ＧＰＳ文字字串
                $GPSAry = explode(',',$ans_val);
                $ret['GPSX'] = isset($GPSAry[0])? $GPSAry[0] : 0;
                $ret['GPSY'] = isset($GPSAry[1])? $GPSAry[1] : 0;
            }
            elseif($isImg == 'Y')
            {
                //圖片路徑
                $ret['wp_work_img_id']  = 0;
                $ret['ans_value']       = '';
                $ret['isLostImg']       = 'Y';
                if(strlen($ans_val) > 10)
                {
                    //產生圖片記錄
                    $filepath = config('mycfg.permit_check_path').date('Y/m/').$work_id.'/';
                    $filename = $img_title.'_'.$id.'_'.time().'.jpg';

                    $wp_work_img_id = $this->createWorkPermitWorkImg($INS,$filepath,$filename,$ans_val,0,$mod_user);

                    //圖片路徑
                    $ret['wp_work_img_id']  = $wp_work_img_id;
                    $ret['ans_value']       = ($wp_work_img_id)? ($filepath.$filename) : '';
                }

            } else {
                //文字紀錄
                $ret['ans_value'] = $ans_val;
            }
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_check_topic_a::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
