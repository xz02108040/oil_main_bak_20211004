<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_rp_member_ei;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\sys_param;
use App\Model\User;
use DB;
use Illuminate\Support\Facades\Lang;

/**
 * 承攬商_成員_擁有的工程身分
 *
 */
trait SupplyMemberIdentityTrait
{
    /**
     * 新增 承攬商_成員_擁有的工程身分[陣列，多筆]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyMemberIdentityGroup($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;

        $identityAry            = isset($data->identity)? $data->identity : 0;
        $b_supply_id            = isset($data->b_supply_id)? $data->b_supply_id : 0;
        $b_cust_id              = isset($data->b_cust_id)? $data->b_cust_id : 0;
        $b_supply_rp_member_id  = isset($data->b_supply_rp_member_id)? $data->b_supply_rp_member_id : 0;
        $b_supply_rp_project_id  = isset($data->b_supply_rp_project_id)? $data->b_supply_rp_project_id : 0;
        if(!$b_supply_id || !$b_cust_id || !count($identityAry)) return $ret;

        foreach ($identityAry as $iid => $val)
        {
            $tmp = [];
            $tmp['b_supply_id']              = $b_supply_id;
            $tmp['b_cust_id']                = $b_cust_id;
            $tmp['type_id']                  = $val['id'];
            $tmp['b_supply_rp_member_id']    = $b_supply_rp_member_id;
            $tmp['b_supply_rp_project_id']    = $b_supply_rp_project_id;
            $tmp['b_supply_rp_member_ei_id'] = $val['b_supply_rp_member_ei_id'];
            $tmp['license']                  = $val['license'];
            $tmp['isIdentity']               = 1;
            $tmp['aproc']                    = 'O';
            $ret = $this->createSupplyMemberIdentity($tmp,$mod_user);
        }
        return $ret;
    }
    /**
     * 新增 承攬商_成員_擁有的工程身分
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyMemberIdentity($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $identityAry= [$identity_A,$identity_B];

        $INS = new b_supply_member_ei();
        $INS->b_supply_id                    = $data->b_supply_id;
        $INS->b_cust_id                      = $data->b_cust_id;
        $INS->engineering_identity_id        = $data->type_id ? $data->type_id : 0;
        $INS->b_supply_rp_member_id          = isset($data->b_supply_rp_member_id) ? $data->b_supply_rp_member_id : 0;
        $INS->b_supply_rp_project_id         = isset($data->b_supply_rp_project_id) ? $data->b_supply_rp_project_id : 0;
        $INS->b_supply_rp_member_ei_id       = isset($data->b_supply_rp_member_ei_id) ? $data->b_supply_rp_member_ei_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
            $aproc      = isset($data->aproc)? $data->aproc : 'X';
            $isIdentity = isset($data->isIdentity)? $data->isIdentity : '';
            //dd($data,$aproc,$isIdentity);
            //如果有 申請單號，則回寫
            if( isset($data->b_supply_rp_member_ei_id) && $data->b_supply_rp_member_ei_id )
            {
                $upAry = ['b_supply_member_ei_id' => $ret];
                DB::table('b_supply_rp_member_ei')
                    ->where('id', $data->b_supply_rp_member_ei_id)
                    ->update($upAry);
            }
            if($aproc == 'O')
            {
                if($isIdentity)
                {
                    $isIns = 0;
                    //COPY 申請單-證照
                    foreach ($data->license as $lid => $val)
                    {
                        $ldata = b_supply_rp_member_l::find($lid);
                        if(isset($ldata))
                        {
                            $tmp = [];
                            $tmp['b_supply_id']             = $ldata->b_supply_id;
                            $tmp['b_cust_id']               = $data->b_cust_id;
                            $tmp['b_supply_member_ei_id']   = $ret;
                            $tmp['b_supply_rp_member_id']   = $INS->b_supply_rp_member_id;
                            $tmp['b_supply_rp_member_ei_id']= $INS->b_supply_rp_member_ei_id;
                            $tmp['b_supply_rp_project_id']  = $INS->b_supply_rp_project_id;
                            $tmp['b_supply_rp_member_l_id'] = $lid;
                            $tmp['e_license_id']            = $ldata->e_license_id;
                            $tmp['license_code']            = $ldata->license_code;
                            $tmp['edate']                   = $ldata->edate;
                            $tmp['file1']                   = '';
                            $tmp['file1N']                   = '';
                            $tmp['file2']                   = '';
                            $tmp['file2N']                   = '';
                            $tmp['file3']                   = '';
                            $tmp['file3N']                   = '';
                            $tmp['filepath1']               = $ldata->file1;
                            $tmp['filepath2']               = $ldata->file2;
                            $tmp['filepath3']               = $ldata->file3;
                            if($this->createSupplyMemberLicense($tmp,$mod_user))
                            {
                                $isIns++;
                            }
                        }
                    }
                } else {
                    //新增 證照
                    if($ret && isset($data->license) && is_array($data->license) && $licenseAmt = count($data->license))
                    {
                        $isIns = 0;
                        foreach ($data->license as $iid => $val)
                        {
                            $tmp = [];
                            $tmp['b_supply_id'] = $data->b_supply_id;
                            $tmp['b_cust_id'] = $data->b_cust_id;
                            $tmp['b_supply_rp_member_id']   = $INS->b_supply_rp_member_id;
                            $tmp['b_supply_rp_member_ei_id']= $INS->b_supply_rp_member_ei_id;
                            $tmp['b_supply_member_ei_id']   = $ret;
                            $tmp['e_license_id']            = $iid;
                            $tmp['edate']                   = isset($val['edate'])? $val['edate'] : date('Y-m-d');
                            $tmp['license_code']            = isset($val['license_code'])? $val['license_code'] : '';
                            $tmp['file1']   = isset($val['file1'])? $val['file1'] : '';
                            $tmp['file1N']  = isset($val['file1N'])? $val['file1N'] : '';
                            $tmp['file2']   = isset($val['file2'])? $val['file2'] : '';
                            $tmp['file2N']  = isset($val['file2N'])? $val['file2N'] : '';
                            $tmp['file3']   = isset($val['file3'])? $val['file3'] : '';
                            $tmp['file3N']  = isset($val['file3N'])? $val['file3N'] : '';
                            //dd($tmp);
                            if($this->createSupplyMemberLicense($tmp,$mod_user))
                            {
                                $isIns++;
                            }
                        }
                        if($isIns !== $licenseAmt)
                        {
                            $ret = -1;
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 修改 承攬商_成員_擁有的工程身分
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyMemberIdentity($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = b_supply_member_ei::find($id);
        if(!isset($UPD->engineering_identity_id)) return $ret;

        //工程身分
        if(isset($data->type_id) && ($data->type_id) && $data->type_id !== $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->type_id;
        }
        //停用
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose        = 'Y';
                $UPD->close_user     = $mod_user;
                $UPD->close_stamp    = $now;
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
     * 取得 承攬商_成員_擁有的工程身分
     *
     * @return array
     */
    public function getApiSupplyMemberIdentityList($sid,$uid,$isClose = '')
    {
        $ret = array();
        $typeAry = b_supply_engineering_identity::getSelect();
        //取第一層
        $data = b_supply_member_ei::where('b_supply_id',$sid)->where('b_cust_id',$uid)->orderby('id','desc');
        if($isClose)
        {
            $data = $data->where('isClose',$isClose);
            if($isClose == 'N')
            {
                $data = $data->where('edate','>=',date('Y-m-d'));
            }
        } else {
            $data = $data->where('isClose','N');
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['type']        = isset($typeAry[$v->engineering_identity_id])? $typeAry[$v->engineering_identity_id] : '';
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員_擁有的工程身分
     *
     * @return array
     */
    public function getApiSupplyMemberIdentity($sid,$uid)
    {
        $ret = array();
        $typeAry = b_supply_engineering_identity::getSelect();
        //取第一層
        $data = b_supply_member_ei::where('b_supply_id',$sid)->where('b_cust_id',$uid)->
                where('isClose','N')->where('edate','>=',date('Y-m-d'))->orderby('id','desc')->get();


        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['type']    = isset($typeAry[$v->engineering_identity_id])? $typeAry[$v->engineering_identity_id] : '';
                $tmp['edate']   = $v->edate;
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 檢查 工作許可證過期者，將其作廢
     *
     * @return array
     */
    public function checkSupplyMemberIdentityOverDate()
    {
        $result = false;
        $uid = 1000000001;
        $now = date('Y-m-d H:i:s');

        //作廢
        $UPD = [];
        $UPD['isClose']     = 'Y';
        $UPD['close_user']  = $uid;
        $UPD['close_stamp'] = $now;
        $UPD['mod_user']    = $uid;
        $UPD['updated_at']  = $now;

        //找到已過期 工作許可證
        $ret = DB::table('b_supply_member_ei')->where('isClose','N')->where('edate','<',date('Y-m-d'));

        //如果有，則作廢
        if($count = $ret->count())
        {
            $result = $ret->update($UPD);
        }

        return [$result,Lang::get('sys_base.base_10139',['name'=>$count])];
    }

}
