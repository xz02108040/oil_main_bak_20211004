<?php

namespace App\Http\Traits\Supply;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_member_l;
use App\Model\User;
use Storage;

/**
 * 承攬商_申請_成員的證照
 *
 */
trait SupplyRPMemberLicenseTrait
{
    /**
     * 申請 承攬商_申請_成員的證照
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPMemberLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;

        $INS = new b_supply_rp_member_l();
        $INS->apply_user                = $mod_user;
        $INS->apply_stamp               = date('Y-m-d H:i:s');
        $INS->b_supply_id               = $data->b_supply_id;
        $INS->b_cust_id                 = $data->b_cust_id;
        $INS->e_license_id              = $data->license_id ? $data->license_id : 0;
        $INS->edate                     = $data->edate ? $data->edate : date('Y-m-d');
        $INS->b_supply_rp_member_id     = isset($data->b_supply_rp_member_id) ? $data->b_supply_rp_member_id : 0;
        $INS->b_supply_rp_member_ei_id  = isset($data->b_supply_rp_member_ei_id) ? $data->b_supply_rp_member_ei_id : 0;
        $INS->b_supply_member_ei_id     = isset($data->b_supply_member_ei_id) ? $data->b_supply_member_ei_id : 0;


        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
            $isUp       = 0;
            $id         = $INS->id;
            $filepath   = config('mycfg.license_path').'RP/'.date('Y/').$id.'/';

            if($data->file1)
            {
                $filename = $id.'_A.'.$data->file1N;
                $file1    = $filepath.$filename;
                if(Storage::put($file1,$data->file1))
                {
                    $isUp++;
                    $INS->file1   = $file1;
                }
            }
            if($data->file2)
            {
                $filename = $id.'_B.'.$data->file2N;
                $file2    = $filepath.$filename;
                if(Storage::put($file2,$data->file2))
                {
                    $isUp++;
                    $INS->file2   = $file2;
                }
            }
            if($data->file3)
            {
                $filename = $id.'_C.'.$data->file3N;
                $file3    = $filepath.$filename;
                if(Storage::put($file3,$data->file3))
                {
                    $isUp++;
                    $INS->file3   = $file3;
                }
            }
            if($isUp) $INS->save();
        }

        return $ret;
    }


    /**
     * 關閉 所有依據申請加入工程案件 相關的工程身份申請單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function closeSupplyRPMemberLicense($b_supply_member_l_id,$b_supply_rp_project_id,$charge_memo,$mod_user = 1)
    {
        if($b_supply_member_l_id)
        {
            $UPD = b_supply_rp_member_l::where('b_supply_member_l_id',$b_supply_member_l_id);
        } else {
            $UPD = b_supply_rp_member_l::where('b_supply_rp_project_id',$b_supply_rp_project_id);
        }
        $UPD->aproc         = 'C';
        $UPD->charge_memo   = $charge_memo;
        $UPD->charge_user   = $mod_user;
        $UPD->charge_stamp  = date('Y-m-d H:i:s');

        return $UPD->save();
    }

    /**
     * 修改 承攬商_成員_擁有的工程身份
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPMemberLicenseGroup($data,$mod_user = 1)
    {
        $isIns = 0;
        $data = (object)$data;

        if(is_array($data) || is_object($data))
        {
            foreach ($data as $lid => $val)
            {
                if($lid && $this->setSupplyRPMemberLicense($lid,$val,$mod_user))
                {
                    $isIns ++;
                }
            }
        }

        return $isIns;
    }

    /**
     * 修改 承攬商_申請_成員的證照
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPMemberLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $aprocAry = array_keys(SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC'));
        $now = date('Y-m-d H:i:s');
        $filepath   = config('mycfg.license_path').'RP/'.date('Y/').$id.'/';

        $isUp = 0;

        $UPD = b_supply_rp_member_l::find($id);
        if(!isset($UPD->e_license_id)) return $ret;


        //審查結果
        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = isset($data->charge_memo)? $data->charge_memo : '';
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //審查通過
            if($data->aproc == 'O' && isset($data->isLicense) && $data->isLicense)
            {
                if(!$this->createSupplyMemberLicense($data,$mod_user))
                {
                    $isUp--;
                }
            }
        }
        //證照
        if(isset($data->e_license_id) && ($data->e_license_id) && $data->e_license_id !== $UPD->e_license_id)
        {
            $isUp++;
            $UPD->e_license_id = $data->e_license_id;
        }
        //證照
        if(isset($data->license_code) && ($data->license_code) && $data->license_code !== $UPD->license_code)
        {
            $isUp++;
            $UPD->license_code = $data->license_code;
        }
        //證照
        if(isset($data->edate) && CheckLib::isDate($data->edate) && $data->edate !== $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //證照檔案
        if(isset($data->file1) && ($data->file1) && isset($data->file1N))
        {
            $filename = $id.'_A.'.$data->file1N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file1))
            {
                $isUp++;
                $UPD->file1 = $file;
            }
        }
        //證照檔案
        if(isset($data->file2) && ($data->file2) && isset($data->file2N))
        {
            $filename = $id.'_B.'.$data->file2N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file2))
            {
                $isUp++;
                $UPD->file2 = $file;
            }
        }
        //證照檔案
        if(isset($data->file3) && ($data->file3) && isset($data->file3N))
        {
            $filename = $id.'_C.'.$data->file3N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file3))
            {
                $isUp++;
                $UPD->file3 = $file;
            }
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
     * 取得 承攬商_成員申請單 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyRPMemberLicenseMainList($aproc = 'A',$rp_member_id = 0,$p_project_id = 0,$rp_member_ei_id = 0)
    {
        $data = b_supply_rp_member_l::join('b_supply as s','s.id','=','b_supply_rp_member_l.b_supply_id')->
        selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
        where('b_supply_rp_member_l.b_supply_rp_member_id',$rp_member_id)->
        where('b_supply_rp_member_l.b_supply_rp_project_id',$p_project_id)->
        where('b_supply_rp_member_l.b_supply_rp_member_ei_id',$rp_member_ei_id)->
        groupby('b_supply_id');

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
     * 取得 承攬商_成員_擁有的證照
     *
     * @return array
     */
    public function getApiSupplyRPMemberLicenseList($sid, $rp_member_id = 0, $rp_project_id = 0, $rp_member_ei_id = 0, $member_ei_id = 0, $aproc = 'A')
    {
        $ret = array();
        $typeAry = e_license::getSelect();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');

        $data    = b_supply_rp_member_l::where('b_supply_id',$sid)->where('isClose','N');
        //dd($rp_member_id,$rp_project_id,$rp_member_ei_id);
        if($rp_member_id >= 0)
        {
            $data    = $data->where('b_supply_rp_member_id',$rp_member_id);
        }
        if($rp_project_id >= 0)
        {
            $data    = $data->where('b_supply_rp_project_id',$rp_project_id);
        }
        if($rp_member_ei_id >= 0)
        {
            $data    = $data->where('b_supply_rp_member_ei_id',$rp_member_ei_id);
        }
        if($member_ei_id)
        {
            $data    = $data->where('b_supply_member_ei_id',$member_ei_id);
        }
        //進度
        if( $aproc && $aproc !== 'X' )
        {
            $data = $data->where('aproc',$aproc);
        }
        $data    = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;
                $filePath1 = strlen($v->file1)? storage_path('app'.$v->file1) : '';
                $filePath2 = strlen($v->file2)? storage_path('app'.$v->file2) : '';
                $filePath3 = strlen($v->file3)? storage_path('app'.$v->file3) : '';

                $data[$k]['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('file/','A'.$id,'sid=RPContractorLicense') : '';
                $data[$k]['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('file/','B'.$id,'sid=RPContractorLicense') : '';
                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('file/','C'.$id,'sid=RPContractorLicense') : '';

                list($show_name1,$show_name2,$show_name3,$show_name4,$show_name5,$edate_type) = e_license::getShowList($v->e_license_id);
                $data[$k]['license']     = isset($typeAry[$v->e_license_id])? $typeAry[$v->e_license_id] : '';
                $data[$k]['show_name1']  = $show_name1;
                $data[$k]['show_name2']  = $show_name2;
                $data[$k]['edate_type']  = $edate_type;
                $data[$k]['aproc_name']  = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_name']  = User::getName($v->apply_user);
                $data[$k]['charge_name'] = User::getName($v->charge_user);
                $data[$k]['user']        = User::getName($v->b_cust_id);
                $data[$k]['type']        = b_supply_member_ei::getName($v->b_supply_member_ei_id);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
