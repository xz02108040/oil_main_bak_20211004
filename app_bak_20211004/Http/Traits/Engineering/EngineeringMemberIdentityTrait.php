<?php

namespace App\Http\Traits\Engineering;

use Session;
use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_license;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_engineering_identity_a;
use App\Model\sys_param;
use App\Model\User;

/**
 * 工程案件_承攬商成員工程身份
 *
 */
trait EngineeringMemberIdentityTrait
{
    /**
     * 新增 承攬商_成員_擁有的工程身分[陣列，多筆]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringMemberIdentityGroup($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;

        $identityAry            = isset($data->identity)? $data->identity : [];
        $e_project_id           = isset($data->e_project_id)? $data->e_project_id : 0;
        $b_supply_id            = isset($data->b_supply_id)? $data->b_supply_id : 0;
        $b_cust_id              = isset($data->b_cust_id)? $data->b_cust_id : 0;
        $b_supply_rp_member_id  = isset($data->b_supply_rp_member_id)? $data->b_supply_rp_member_id : 0;
        $b_supply_rp_project_id = isset($data->b_supply_rp_project_id)? $data->b_supply_rp_project_id : 0;
        if(!$b_supply_id || !$b_cust_id || !count($identityAry)) return $ret;
        foreach ($identityAry as $iid => $val)
        {
            $licenseAry = $val['license'];
            $b_supply_rp_project_license_id = $val['b_supply_rp_project_license_id'];

            if(!isset($licenseAry['e_license_id'])) continue;
            //必須先建立 證照
            $tmp = [];
            $tmp['b_supply_id']             = $b_supply_id;
            $tmp['b_cust_id']               = $b_cust_id;
            $tmp['b_supply_member_ei_id']   = 0;
            $tmp['b_supply_rp_member_id']   = $b_supply_rp_member_id;
            $tmp['b_supply_rp_member_ei_id']= 0;
            $tmp['b_supply_rp_project_id']  = $b_supply_rp_project_id;
            $tmp['b_supply_rp_member_l_id'] = 0;
            $tmp['license_id']              = $licenseAry['e_license_id'];
            $tmp['license_code']            = $licenseAry['license_code'];
            $tmp['edate_type']              = $licenseAry['edate_type'] ? $licenseAry['edate_type'] : 1;
            $tmp['sdate']                   = $licenseAry['sdate'];
            $tmp['file1']                   = '';
            $tmp['file1N']                  = '';
            $tmp['file2']                   = '';
            $tmp['file2N']                  = '';
            $tmp['file3']                   = '';
            $tmp['file3N']                  = '';
            $tmp['filepath1']               = $licenseAry['file1'];
            $tmp['filepath2']               = $licenseAry['file2'];
            $tmp['filepath3']               = $licenseAry['file3'];
            $b_supply_member_l_id = $this->createSupplyMemberLicense($tmp,$mod_user);

            if($licenseAry['e_license_id'] == 1 && $licenseAry['license_code2'])
            {
                $tmp = [];
                $tmp['b_supply_id']             = $b_supply_id;
                $tmp['b_cust_id']               = $b_cust_id;
                $tmp['b_supply_member_ei_id']   = 0;
                $tmp['b_supply_rp_member_id']   = $b_supply_rp_member_id;
                $tmp['b_supply_rp_member_ei_id']= 0;
                $tmp['b_supply_rp_project_id']  = $b_supply_rp_project_id;
                $tmp['b_supply_rp_member_l_id'] = 0;
                $tmp['license_id']              = 14;
                $tmp['license_code']            = $licenseAry['license_code2'];
                $tmp['edate_type']              = $licenseAry['edate_type'] ? $licenseAry['edate_type'] : 1;
                $tmp['sdate']                   = $licenseAry['sdate2'];
                $tmp['file1']                   = '';
                $tmp['file1N']                  = '';
                $tmp['file2']                   = '';
                $tmp['file2N']                  = '';
                $tmp['file3']                   = '';
                $tmp['file3N']                  = '';
                $tmp['filepath1']               = $licenseAry['file4'];
                $tmp['filepath2']               = '';
                $tmp['filepath3']               = '';
                $b_supply_member_l_id2 = $this->createSupplyMemberLicense($tmp,$mod_user);
//                dd($b_supply_member_l_id2,$tmp);
            }

            if($b_supply_member_l_id)
            {
                $tmp = [];
                if(in_array($iid,[1,2]))
                {
                    //需要工安課審查
                    $tmp['b_cust_id']                = $b_cust_id;
                    $tmp['aproc']                    = 'P';
                    $tmp['b_supply_member_l_id']     = $b_supply_member_l_id;
                    $this->setSupplyRPProjectMemberLicense($b_supply_rp_project_license_id,$tmp,$mod_user);
                } else {
                    //在建立工程身分
                    $tmp['e_project_id']             = $e_project_id;
                    $tmp['b_supply_id']              = $b_supply_id;
                    $tmp['b_cust_id']                = $b_cust_id;
                    $tmp['b_supply_member_l_id']     = $b_supply_member_l_id;
                    $tmp['b_supply_member_l_id2']    = isset($b_supply_member_l_id2) ? $b_supply_member_l_id2 : 0;
                    $tmp['engineering_identity_id']  = $iid;
                    $tmp['isIdentity']               = 1;
                    $tmp['aproc']                    = 'O';
                    $ret = $this->createEngineeringMemberIdentity($tmp,$mod_user);
                }

            }

        }
        return $ret;
    }
    /**
     * 新增 工程案件_承攬商成員工程身份
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringMemberIdentity($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if (!isset($data->e_project_id) || !isset($data->engineering_identity_id)) return $ret;
        //2021-06-16 新增判斷，若為補工程身分，語法改為使用更新
        $Key = array();
        $Key['e_project_id']                      = $data->e_project_id;
        $Key['b_cust_id']                         = $data->b_cust_id;
        $Key['engineering_identity_id']           = $data->engineering_identity_id;
        $Key['isClose']                           = 'N';
        $old_data = e_project_license::where($Key)->where('b_supply_member_l_id','0')->get()->toArray();
        $result = false;
        if (!empty($old_data)) {
            foreach ($old_data as $val) {
                $result = $this->setEngineeringMemberIdentity($val['id'], $data, $mod_user);
            }
        }

        if ($result) {
            $ret = True;
            return $ret;
        } else {
            $INS = new e_project_license();
            $INS->e_project_id                      = $data->e_project_id;
            $INS->b_supply_id                       = $data->b_supply_id;
            $INS->b_cust_id                         = $data->b_cust_id;
            $INS->engineering_identity_id           = $data->engineering_identity_id;
            $INS->b_supply_member_l_id              = isset($data->b_supply_member_l_id) ? $data->b_supply_member_l_id : 0;
            $INS->b_supply_member_l_id2             = isset($data->b_supply_member_l_id2) ? $data->b_supply_member_l_id2 : 0;
            $INS->b_supply_rp_project_license_id    = isset($data->b_supply_rp_project_license_id) ? $data->b_supply_rp_project_license_id : 0;

            $INS['new_user']      = $mod_user;
            $ret = ($INS->save())? $INS->id : 0;
        }

        if($ret)
        {
            //如果是　工安＆工負
            $identity_A       = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
            $identity_B       = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
            $identity_C       = sys_param::getParam('SUPPLY_RP_BCUST_IDENTITY_ID',9);
            if( in_array($INS->engineering_identity_id,[$identity_A,$identity_B,$identity_C]))
            {
                //更新中油標籤
                $cpc_tag  = ($INS->engineering_identity_id == $identity_A)? 'A' : 'B';
                $job_kind = ($INS->engineering_identity_id == $identity_A)? 2 : 3;
                $cpc_tag  = ($INS->engineering_identity_id == $identity_C)? 'C' : $cpc_tag;
                $job_kind = ($INS->engineering_identity_id == $identity_C)? 1 : $job_kind;
                $this->chgEngineeringMemberCPCTag($INS->e_project_id,$INS->b_cust_id,$cpc_tag,$job_kind,$mod_user);
            }
        }
        return $ret;
    }

    /**
     * 修改 工程案件_承攬商成員工程身份
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringMemberIdentity($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = e_project_license::find($id);
        if(!isset($UPD->engineering_identity_id)) return $ret;
        //工程身份
        if(isset($data->engineering_identity_id) && is_numeric($data->engineering_identity_id) && $data->engineering_identity_id != $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->engineering_identity_id;
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
        //承攬商成員之證照ID
        if(isset($data->b_supply_member_l_id) && is_numeric($data->b_supply_member_l_id) && $data->b_supply_member_l_id != $UPD->b_supply_member_l_id)
        {
            $isUp++;
            $UPD->b_supply_member_l_id = $data->b_supply_member_l_id;
        }
        //承攬商成員之證照ID2
        if(isset($data->b_supply_member_l_id2) && is_numeric($data->b_supply_member_l_id2) && $data->b_supply_member_l_id2 != $UPD->b_supply_member_l_id2)
        {
            $isUp++;
            $UPD->b_supply_member_l_id2 = $data->b_supply_member_l_id2;
        }
        //承攬商ID
        if(isset($data->b_supply_id) && is_numeric($data->b_supply_id) && $data->b_supply_id != $UPD->b_supply_id)
        {
            $isUp++;
            $UPD->b_supply_id = $data->b_supply_id;
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
     * 取得 工程案件_承攬商成員工程身份
     *
     * @return array
     */
    public function getApiEngineeringMemberIdentityList($project_id,$user_id)
    {
        $ret = array();
        if(!$project_id ) return $ret;
        $identityAry= b_supply_engineering_identity::getSelect(0);
        $user       = User::getName($user_id);
        //取第一層
        $data = e_project_license::
                where('e_project_id',$project_id)->where('b_cust_id',$user_id)->
                orderby('isClose')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['identity']    = isset($identityAry[$v->engineering_identity_id])? $identityAry[$v->engineering_identity_id] : '';
                $data[$k]['license']     = b_supply_member_l::getLicenseInfo($v->b_supply_member_l_id);;
                $data[$k]['user']        = $user;
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工程案件_工程身份「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringLicense($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        //取第一層
        $data = e_project_l::
        join('b_supply_engineering_identity as el','e_project_l.engineering_identity_id','=','el.id')->
        select('el.id','el.name as name')->
        where('e_project_l.e_project_id',$pid)->where('e_project_l.isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']              = $v->id;
                $tmp['name']            = $v->name;
                $ret[$v->id] = $tmp;
            }
        }

        return $ret;
    }

}
