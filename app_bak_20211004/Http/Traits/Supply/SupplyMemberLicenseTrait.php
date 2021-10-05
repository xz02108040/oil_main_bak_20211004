<?php

namespace App\Http\Traits\Supply;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_l;
use App\Model\User;
use Storage;
use DB;

/**
 * 承攬商_成員_擁有的證照
 *
 */
trait SupplyMemberLicenseTrait
{
    /**
     * 新增 承攬商_成員_擁有的證照
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyMemberLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_cust_id)) return $ret;

        $INS = new b_supply_member_l();
        $INS->b_supply_id               = $data->b_supply_id;
        $INS->b_cust_id                 = $data->b_cust_id;
        $INS->b_supply_member_ei_id     = isset($data->b_supply_member_ei_id)? $data->b_supply_member_ei_id : 0;
        $INS->b_supply_rp_member_id     = isset($data->b_supply_rp_member_id)? $data->b_supply_rp_member_id : 0;
        $INS->b_supply_rp_member_ei_id  = isset($data->b_supply_rp_member_ei_id)? $data->b_supply_rp_member_ei_id : 0;
        $INS->b_supply_rp_member_l_id   = isset($data->b_supply_rp_member_l_id)? $data->b_supply_rp_member_l_id : 0;
        $INS->b_supply_rp_project_id    = isset($data->b_supply_rp_project_id)? $data->b_supply_rp_project_id : 0;
        $INS->e_license_id              = $data->license_id ? $data->license_id : 0;
        $INS->license_code              = isset($data->license_code) ? $data->license_code : '';
        $INS->edate_type                = isset($data->edate_type) ? $data->edate_type : 1;
        $INS->sdate                     = $data->sdate ? $data->sdate : date('Y-m-d');
        //計算有限期限
        list($issuing_kind,$limitYear1,$limitYear2) = e_license::getIssuingList($INS->e_license_id);
        //回訓計算
        if($issuing_kind == 1 && $INS->edate_type == 2)
        {
            $INS->edate = date('Y-m-d', strtotime($INS->sdate.'+'.$limitYear2.' years'));
        } else {
            $INS->edate = date('Y-m-d', strtotime($INS->sdate.'+'.$limitYear1.' years'));
        }

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        //dd($ret,$data->filepath1);
        if($ret)
        {
            $isUp       = 0;
            $filepath   = config('mycfg.license_path').date('Y/').$INS->id.'/';

            if(!$data->file1 && isset( $data->filepath1 ) && $data->filepath1){
                $isUp++;
                $INS->file1   = $data->filepath1;
            }
            if(!$data->file2 && isset( $data->filepath2 ) && $data->filepath2){
                $isUp++;
                $INS->file2   = $data->filepath2;
            }
            if(!$data->file3 && isset( $data->filepath3 ) && $data->filepath3){
                $isUp++;
                $INS->file3   = $data->filepath3;
            }

            if($data->file1)
            {
                $filename = $data->b_cust_id.'_A.'.$data->file1N;
                $file1    = $filepath.$filename;
                if(Storage::put($file1,$data->file1))
                {
                    $isUp++;
                    $INS->file1   = $file1;
                }
            }
            if($data->file2)
            {
                $filename = $data->b_cust_id.'_B.'.$data->file2N;
                $file2    = $filepath.$filename;
                if(Storage::put($file2,$data->file2))
                {
                    $isUp++;
                    $INS->file2   = $file2;
                }
            }
            if($data->file3)
            {
                $filename = $data->b_cust_id.'_C.'.$data->file3N;
                $file3    = $filepath.$filename;
                if(Storage::put($file3,$data->file3))
                {
                    $isUp++;
                    $INS->file3   = $file3;
                }
            }
            if($isUp) $INS->save();

            //如果有 申請單號，則回寫
            if( isset($data->b_supply_rp_member_l_id) && $data->b_supply_rp_member_l_id )
            {
                $upAry = ['b_supply_member_l_id' => $INS->id];
                $upAry['aproc']         = 'O';
                $upAry['charge_user']   = $mod_user;
                $upAry['charge_stamp']  = date('Y-m-d H:i:s');
                DB::table('b_supply_rp_member_l')
                    ->where('id', $data->b_supply_rp_member_l_id)
                    ->update($upAry);
            }
            //回寫 最小的有效日期
            if( isset($data->b_supply_member_ei_id) && $data->b_supply_member_ei_id )
            {
                $edate = $INS->edate;
                DB::table('b_supply_member_ei')
                    ->where('id', $data->b_supply_member_ei_id)
                    ->where(function ($query) use ($edate) {
                        $query->where('edate', '<', $edate)
                            ->orWhereRaw('edate is NULL');
                    })
                    ->update(['edate' => $edate]);
            }
        }

        return $ret;
    }

    /**
     * 修改 承攬商_成員_擁有的證照
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyMemberLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $filepath   = config('mycfg.license_path').date('Y/').$id.'/';

        $isUp = 0;

        $UPD = b_supply_member_l::find($id);
        if(!isset($UPD->e_license_id)) return $ret;

        //證照
        if(isset($data->e_license_id) && ($data->e_license_id) && $data->e_license_id !== $UPD->e_license_id)
        {
            $isUp++;
            $UPD->e_license_id = $data->e_license_id;
        }
        //證照代碼
        if(isset($data->license_code) && ($data->license_code) && $data->license_code !== $UPD->license_code)
        {
            $isUp++;
            $UPD->license_code = $data->license_code;
        }
        //發證類型
        if(isset($data->edate_type) && ($data->edate_type) && $data->edate_type !== $UPD->edate_type)
        {
            $isUp++;
            $UPD->edate_type = $data->edate_type;
        }
        //發證日期 與 有效期限
        if(isset($data->sdate) && CheckLib::isDate($data->sdate) && $data->sdate !== $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
            //計算有限期限
            list($issuing_kind,$limitYear1,$limitYear2) = e_license::getIssuingList($UPD->e_license_id);
            //回訓計算
            if($issuing_kind == 1 && $UPD->edate_type == 2)
            {
                $UPD->edate = date('Y-m-d', strtotime($UPD->sdate.'+'.$limitYear2.' years'));
            } else {
                $UPD->edate = date('Y-m-d', strtotime($UPD->sdate.'+'.$limitYear1.' years'));
            }

//            $edate      = $UPD->edate;
//            DB::table('b_supply_member_ei')
//                ->where('id', $UPD->b_supply_member_ei_id)
//                ->where(function ($query) use ($edate) {
//                    $query->where('edate', '<', $edate)
//                        ->orWhereRaw('edate is NULL');
//                })
//                ->update(['edate' => $edate]);
        }
        //證照檔案
        if(isset($data->file1) && ($data->file1) && $data->file1N)
        {
            $filename = $UPD->b_cust_id.'_A.'.$data->file1N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file1))
            {
                $isUp++;
                $UPD->file1 = $file;
            }
        }
        //證照檔案
        if(isset($data->file2) && ($data->file2) && $data->file2N)
        {
            $filename = $UPD->b_cust_id.'_B.'.$data->file2N;
            $file    = $filepath.$filename;
            if(Storage::put($file,$data->file2))
            {
                $isUp++;
                $UPD->file2 = $file;
            }
        }
        //證照檔案
        if(isset($data->file3) && ($data->file3) && $data->file3N)
        {
            $filename = $UPD->b_cust_id.'_C.'.$data->file3N;
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
     * 取得 承攬商_成員_擁有的證照
     *
     * @return array
     */
    public function getApiSupplyMemberLicenseList($b_supply_id = 0, $b_cust_id, $member_ei_id = 0, $isClose = 'N')
    {
        $ret = array();
        $edateTypeAry = SHCSLib::getCode('LICENSE_ISSUING_KIND2');
        $data    = b_supply_member_l::where('b_supply_member_l.b_cust_id',$b_cust_id)->
                    join('e_license as e','e.id','=','b_supply_member_l.e_license_id');
        if ($b_supply_id) {
            $data    = $data->where('b_supply_member_l.b_supply_id', $b_supply_id);
        }
        if ($member_ei_id) {
            $data    = $data->where('b_supply_member_l.b_supply_member_ei_id', $member_ei_id);
        }
        if ($isClose) {
            $data    = $data->where('b_supply_member_l.isClose', $isClose);
        }
        $data    = $data->select('b_supply_member_l.*','e.name','e.license_show_name1',
            'e.license_show_name2','e.license_show_name3','e.license_show_name4','e.license_show_name5',
            'e.license_show_name6')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;
                $filePath1 = strlen($v->file1)? storage_path('app'.$v->file1) : '';
                $filePath2 = strlen($v->file2)? storage_path('app'.$v->file2) : '';
                $filePath3 = strlen($v->file3)? storage_path('app'.$v->file3) : '';

                $data[$k]['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('img/License/',$id,'sid=A') : '';
                $data[$k]['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('img/License/',$id,'sid=B') : '';
                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('img/License/',$id,'sid=C') : '';
//                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('file/','C'.$id,'sid=ContractorLicense') : '';

                $data[$k]['b_supply_id'] = $v->b_supply_id;
                $data[$k]['license']     = $v->name;
                $data[$k]['edate_type_name']  = isset($edateTypeAry[$v->edate_type])? $edateTypeAry[$v->edate_type] : '';
                $data[$k]['show_name1']  = $v->license_show_name1;
                $data[$k]['show_name2']  = $v->license_show_name2;
                $data[$k]['show_name3']  = $v->license_show_name3;
                $data[$k]['show_name4']  = $v->license_show_name4;
                $data[$k]['show_name5']  = $v->license_show_name5;
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員_擁有的證照 For APP
     *
     * @return array
     */
    public function getApiSupplyMemberLicense($b_supply_id, $b_cust_id)
    {
        $ret = array();
        $edateTypeAry = SHCSLib::getCode('LICENSE_ISSUING_KIND2');
        $data    = b_supply_member_l::where('b_supply_member_l.b_supply_id',$b_supply_id)->
        where('b_supply_member_l.b_cust_id',$b_cust_id)->where('b_supply_member_l.isClose','N')->
        join('e_license as e','e.id','=','b_supply_member_l.e_license_id');
        $data    = $data->select('b_supply_member_l.sdate','b_supply_member_l.edate','b_supply_member_l.license_code',
            'b_supply_member_l.edate_type','e.name','e.license_show_name1','e.license_show_name2',
            'e.license_show_name3','e.license_show_name4','e.license_show_name5','e.license_show_name6')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['name']                = $v->name;
                $tmp['sdate']               = $v->sdate;
                $tmp['sdate_title']         = $v->license_show_name2;
                $tmp['edate']               = $v->edate;
                $tmp['license_code']        = $v->license_code;
                $tmp['license_code_title']  = $v->license_show_name1;
                $tmp['edate_type_name']     = isset($edateTypeAry[$v->edate_type])? $edateTypeAry[$v->edate_type] : '';
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 取得 承攬商_成員_單筆證照
     *
     * @return array
     */
    public function getApiSupplyMemberLicenseData($id,$isClose = '')
    {
        $ret = array();
        $typeAry      = e_license::getSelect();
        $closeAry     = SHCSLib::getCode('CLOSE');
        $edateTypeAry = SHCSLib::getCode('LICENSE_ISSUING_KIND2');
        $data         = b_supply_member_l::where('id',$id);
        if($isClose)
        {
            $data = $data->where('isClose',$isClose);
        }
        $data    = $data->first();
        if(isset($data->id))
        {
            $id        = $data->id;
            $filePath1 = strlen($data->file1)? storage_path('app'.$data->file1) : '';
            $filePath2 = strlen($data->file2)? storage_path('app'.$data->file2) : '';
            $filePath3 = strlen($data->file3)? storage_path('app'.$data->file3) : '';

            $data['filePath1'] = ($filePath1 && file_exists($filePath1))? SHCSLib::url('img/License/',$id,'sid=A') : '';
            $data['filePath2'] = ($filePath2 && file_exists($filePath2))? SHCSLib::url('img/License/',$id,'sid=B') : '';
            $data['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('img/License/',$id,'sid=C') : '';
//                $data[$k]['filePath3'] = ($filePath3 && file_exists($filePath3))? SHCSLib::url('file/','C'.$id,'sid=ContractorLicense') : '';

            list($show_name1,$show_name2,$show_name3,$show_name4,$show_name5,$edate_type) = e_license::getShowList($data->e_license_id,$data->edate_type);
            $data['license']            = isset($typeAry[$data->e_license_id])? $typeAry[$data->e_license_id] : '';
            $data['edate_type_name']    = isset($edateTypeAry[$data->edate_type])? $edateTypeAry[$data->edate_type] : '';
            $data['show_name1']  = $show_name1;
            $data['show_name2']  = $show_name2;
            $data['show_name3']  = $show_name3;
            $data['show_name4']  = $show_name4;
            $data['show_name5']  = $show_name5;
            $data['edate_type']  = $edate_type;
            $data['isCloseName'] = isset($closeAry[$data->isClose])? $closeAry[$data->isClose] : '';
            $data['close_user']  = User::getName($data->close_user);
            $data['new_user']    = User::getName($data->new_user);
            $data['mod_user']    = User::getName($data->mod_user);
            $ret = (object)$data;
        }

        return $ret;
    }

}
