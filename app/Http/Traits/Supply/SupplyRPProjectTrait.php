<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_project;
use App\Model\Supply\b_supply_car_type;
use App\Model\Supply\b_supply_rp_bcust;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Supply\b_supply_rp_member;
use App\Model\Supply\b_supply_rp_project;
use App\Model\User;
use Storage;
use Lang;

/**
 * 承攬商_申請_加入工程案件
 *
 */
trait SupplyRPProjectTrait
{
    /**
     * 新增 承攬商_申請_加入工程案件
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPProject($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;

        $INS = new b_supply_rp_project();
        $INS->apply_user    = $mod_user;
        $INS->apply_stamp   = date('Y-m-d H:i:s');
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_cust_id     = $data->b_cust_id;
        $INS->e_project_id  = $data->e_project_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
            $data->b_supply_rp_project_id =$ret;
            //新增 工程身份-施工人員「多筆」
            if(count($data->identity))
            {
                foreach ($data->identity as $val)
                {
                    $id     = isset($val['id'])? $val['id'] : 0;
                    $isOk   = isset($val['isOk'])? $val['isOk'] : 'N';
                    $license= isset($val['license'])? $val['license'] : [];

                    if($id && $isOk && count($license))
                    {
                        $tmp = [];
                        $tmp['b_supply_id']              = $data->b_supply_id;
                        $tmp['b_cust_id']                = $data->b_cust_id;
                        $tmp['apply_user']               = $mod_user;
                        $tmp['apply_stamp']              = date('Y-m-d H:i:s');
                        $tmp['type_id']                  = $id;
                        $tmp['b_supply_rp_project_id']   = $data->b_supply_rp_project_id;
                        $tmp['license']                  = $license;
                        $ret = $this->createSupplyRPMemberIdentity($tmp,$mod_user);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 修改 承攬商_申請_加入工程案件
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPProject($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_project::find($id);
        if(!isset($UPD->b_cust_id)) return $ret;
        //
        if(isset($data->e_project_id) && ($data->e_project_id) && $data->e_project_id !== $UPD->e_project_id)
        {
            $isUp++;
            $UPD->e_project_id = $data->e_project_id;
        }
        //
        if(isset($data->aproc) && ($data->aproc) && $data->aproc !== $UPD->aproc)
        {
            $isOk = 0;
            if($data->aproc == 'O')
            {
                $data->isCreateIdentity = 1;
                if($this->createEngineeringMember($data,$mod_user))
                {
                    $isOk = 1;
                }
            } elseif($data->aproc == 'C') {
                $isOk = 1;
                //如果是不同意
                $tmp = [];
                $tmp['aproc']           = 'C';
                $tmp['charge_user1']    = $mod_user;
                $tmp['charge_stamp1']   = $now;
                $tmp['charge_memo1']    = isset($data->charge_memo)? $data->charge_memo : '';
                $tmp['mod_user']        = $mod_user;
                $tmp['updated_at']      = $now;
                \DB::table('b_supply_rp_project_license')->where('b_supply_rp_project_id',$id)->update($tmp);
            } else {
                $isOk = 1;
            }
            if($isOk)
            {
                $isUp++;
                $UPD->aproc         = $data->aproc;
                $UPD->charge_memo   = isset($data->charge_memo)? $data->charge_memo : '';
                $UPD->charge_user   = $mod_user;
                $UPD->charge_stamp  = $now;
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
     * 取得 承攬商_成員申請單 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyRPProjectMainList($aproc = 'A',$allowAry = [])
    {
        $data = b_supply_rp_project::join('b_supply as s','s.id','=','b_supply_rp_project.b_supply_id')->
        selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
        groupby('b_supply_id');

        if(is_array($allowAry) && count($allowAry))
        {
            $data = $data->whereIn('b_supply_rp_project.e_project_id',$allowAry);
        }
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 承攬商_申請_加入工程案件
     *
     * @return array
     */
    public function getApiSupplyRPProjectList($sid,$aproc = 'A',$allowAry = [])
    {
        $ret = array();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //取第一層
        $data = b_supply_rp_project::where('b_supply_id',$sid)->where('aproc',$aproc);
        if(is_array($allowAry) && count($allowAry))
        {
            $data = $data->whereIn('e_project_id',$allowAry);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id = $v->id;

                $data[$k]['aproc_name']     = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_name']     = User::getName($v->apply_user);
                $data[$k]['charge_name']    = User::getName($v->charge_user);
                $data[$k]['user']           = User::getName($v->b_cust_id);
                $data[$k]['project']        = e_project::getName($v->e_project_id,2);
                $data[$k]['chg_user']       = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
