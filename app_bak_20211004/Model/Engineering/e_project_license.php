<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_license extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_license';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($project_id, $b_cust_id, $engineering_identity_id, $extid = 0)
    {
        if(!$project_id || !$b_cust_id || !$engineering_identity_id) return 0;
        $data  = e_project_license::where('e_project_id',$project_id)->where('b_cust_id',$b_cust_id)->
        where('engineering_identity_id',$engineering_identity_id)->
        where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    /**
     *  是否存在【工安】【工負】工程身分
     * @param $id
     * @return int
     */
    protected function isWhoIdenttity($project_id, $b_cust_id)
    {
        if(!$project_id || !$b_cust_id) return 0;
        $isAd1 = $isAd2 = $isAd3 = 0;

        $identity_A       = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B       = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $identity_C       = sys_param::getParam('SUPPLY_RP_BCUST_IDENTITY_ID',9);


        $data = e_project_license::where('e_project_id',$project_id)->where('b_cust_id',$b_cust_id)->where('isClose','N');
        $data = $data->select('engineering_identity_id')->orderby('engineering_identity_id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if($val->engineering_identity_id == $identity_A)
                {
                    $isAd1++;
                }
                if($val->engineering_identity_id == $identity_B)
                {
                    $isAd2++;
                }
                if($val->engineering_identity_id == $identity_C)
                {
                    $isAd3++;
                }
            }
        }

        return $isAd1? $identity_A : ($isAd2 ? $identity_B : $identity_C);
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function getUserIdentity($project_id, $b_cust_id,$identityAry = [])
    {
        $ret = [];
        if(!$project_id || !$b_cust_id ) return $ret;
        $data  = e_project_license::
        join('b_supply_engineering_identity as i','i.id','=','e_project_license.engineering_identity_id')->
        where('e_project_license.e_project_id',$project_id)->where('e_project_license.b_cust_id',$b_cust_id)->
        where('e_project_license.isClose','N')->select('i.id','i.name');
        if(is_array($identityAry) && count($identityAry))
        {
            $data = $data->whereIn('i.id',$identityAry);
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[] = ['id'=>$val->id,'name'=>$val->name];
            }
        }
        return $ret;
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function getUserIdentityAllName($project_id, $b_cust_id, $showType = 0)
    {
        $ret    = [];
        $aWhere = [];
        if(!$project_id || !$b_cust_id ) return ($showType)? $ret : '';
        list($project_aproc,$project_edate) = e_project::getProjectList1($project_id);

        //案件為過期階段，要顯示已停用的工程身分
        if (!in_array($project_aproc, ['O','C'])) {
            $aWhere = ['e_project_license.isClose' =>'N'];
        }

        $data  = e_project_license::
        join('b_supply_engineering_identity as i','i.id','=','e_project_license.engineering_identity_id')->
        where('e_project_license.e_project_id',$project_id)->where('e_project_license.b_cust_id',$b_cust_id)->
        where($aWhere)->select('i.id','i.name');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                if($showType == 2)
                {
                    $tmp = [];
                    $tmp['id']      = $val->id;
                    $tmp['name']    = $val->name;
                    $ret[] = $tmp;
                } else {
                    $ret[$val->id] = $val->name;
                }
            }
        }
        return ($showType)?  $ret : implode('，',$ret);
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function getUserIdentitylicenseCode($project_id, $b_cust_id, $identtity)
    {
        $ret = '';
        if(!$project_id || !$b_cust_id || !$identtity) return $ret;
        $data  = e_project_license::
        join('b_supply_member_l as sl','sl.id','=','e_project_license.b_supply_member_l_id')->
        where('e_project_license.e_project_id',$project_id)->where('e_project_license.b_cust_id',$b_cust_id)->
        where('e_project_license.engineering_identity_id',$identtity)->
        where('e_project_license.isClose','N')->select('sl.license_code');
        $data = $data->first();
        if(isset($data->license_code))
        {
            $ret = $data->license_code;
        }
        return $ret;
    }

     /**
     *  是否存在【工安】【工負】工程身分
     * @param $id
     * @return int
     */
    protected function isAd($project_id, $b_cust_id, $engineering_identity_id, $extid = 0)
    {
        if(!$project_id || !$b_cust_id || !$engineering_identity_id) return 0;
        //取的 工安&工負 ID
        $IDENTITY_ROOT = sys_param::getParam('IDENTITY_ROOT_ID');
        $IDENTITY_ROOT_ARY = explode(',',$IDENTITY_ROOT);
        if(!in_array($engineering_identity_id,$IDENTITY_ROOT_ARY)) return 0;
        foreach ($IDENTITY_ROOT_ARY as $key => $value) {
            if ($value == $engineering_identity_id) {
                unset($IDENTITY_ROOT_ARY[$key]);
            }
        }

        $data  = e_project_license::where('e_project_id',$project_id)->where('b_cust_id',$b_cust_id)->
                whereIn('engineering_identity_id',$IDENTITY_ROOT_ARY)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$isAddBase = 1 ,$isFirst = 1,$extAry = [])
    {
        $ret  = [];
        $data = e_project_l::where('e_project_id',$project_id)->
        select('id','engineering_identity_id')->where('isClose','N');

        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        //加入 工負＆公安＆施工人員
        if($isAddBase)
        {
            $identityParam = sys_param::getParam('IDENTITY_BASE_ID');
            $identityAry   = explode(',',$identityParam);
            if(count($identityAry))
            {
                foreach ($identityAry as $val)
                {
                    $ret[$val] = b_supply_engineering_identity::getName($val);
                }
            }
        }
        foreach ($data as $key => $val)
        {
            if(!in_array($val->engineering_identity_id,$extAry))
            {
                $ret[$val->engineering_identity_id] = b_supply_engineering_identity::getName($val->engineering_identity_id);
            }
        }

        return $ret;
    }

    /**
     *  檢查該成員的證照是否存在
     *  b_supply_member_l_id = 0為人工強制寫入，需判斷可另外功能申請補證
     *  回傳Y表示該人員身份正常，N為失效
     * @param $id
     * @return boolen
     */
    protected function getUserlicense($project_id, $b_cust_id, $engineering_identity_id = 0, $default = 'N')
    {
        $ret = $default;
        $data  = e_project_license::where('e_project_license.e_project_id', $project_id)->where('e_project_license.b_cust_id', $b_cust_id)->where('e_project_license.isClose', 'N')->select('b_supply_member_l_id', 'id');
        if ($engineering_identity_id) {
            $data = $data->where('engineering_identity_id', $engineering_identity_id);
        }
        $data = $data->get();
        
        //只要到一筆檢查到失效(N)的資格，就直接回傳'N'
        foreach ($data as $val) {
            if ($val->b_supply_member_l_id != 0) {
                $ret = 'Y';
            } else {
                $ret = 'N';
                break;
            }
        }

        return $ret;
    }

    /**
     *  停用該人員所有工程身分
     * @param $id
     * @return int
     */
    protected function closeUserIdentity($b_cust_id, $e_project_id = 0, $mod_user = 1, $b_supply_rp_project_license_id = 0)
    {
        if (!$b_cust_id) return 0;
        $now = date('Y-m-d H:i:s');

        $res  = e_project_license::where('b_cust_id', $b_cust_id)
            ->where('isClose', 'N');
        // 工程案件ID
        if ($e_project_id) {
            $res  = $res->where('e_project_id', $e_project_id);
        }
        //申請單ID
        if ($b_supply_rp_project_license_id) {
            $res  = $res->where('b_supply_rp_project_license_id', $b_supply_rp_project_license_id);
        }
        $res  = $res->get();
        foreach ($res as $val) {
            $UPD = e_project_license::find($val->id);
            $UPD->isClose = 'Y';
            $UPD->close_user = $mod_user;
            $UPD->close_stamp = $now;
            $UPD->save();
        }
        return $res->count();
    }
}
