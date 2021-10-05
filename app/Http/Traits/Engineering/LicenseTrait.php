<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_license_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_code;
use App\Model\User;

/**
 * 證照
 *
 */
trait LicenseTrait
{
    /**
     * 新增 證照
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
        $today = date('Y-m-d');

        $INS = new e_license();
        $INS->name          = $data->name;
        $INS->license_type  = $data->license_type ? $data->license_type : 1;
        $INS->license_issuing_kind      = $data->license_issuing_kind ? $data->license_issuing_kind : 1;
        $INS->sdate         = $data->sdate ? $data->sdate : $today;
        $INS->edate         = $data->edate ? $data->edate : '9999-12-31';
        if(isset($data->license_show_name1) && $data->license_show_name1)
        {
            $INS->license_show_name1  = $data->license_show_name1;
        }
        if(isset($data->license_show_name2) && $data->license_show_name2)
        {
            $INS->license_show_name2  = $data->license_show_name2;
        }
        if(isset($data->license_show_name3))
        {
            $INS->license_show_name3  = $data->license_show_name3;
        }
        if(isset($data->license_issuing_kind3))
        {
            $INS->license_issuing_kind3  = $data->license_issuing_kind3;
        }
        if(isset($data->license_show_name4))
        {
            $INS->license_show_name4  = $data->license_show_name4;
        }
        if(isset($data->license_issuing_kind4))
        {
            $INS->license_issuing_kind4  = $data->license_issuing_kind4;
        }
        if(isset($data->license_show_name5))
        {
            $INS->license_show_name5  = $data->license_show_name5;
        }
        if(isset($data->license_issuing_kind5))
        {
            $INS->license_issuing_kind5  = $data->license_issuing_kind5;
        }
        if(isset($data->edate_limit_year1) && $data->edate_limit_year1)
        {
            $INS->edate_limit_year1  = $data->edate_limit_year1;
        }
        if(isset($data->edate_limit_year2) && $data->edate_limit_year2)
        {
            $INS->edate_limit_year2  = $data->edate_limit_year2;
        }
        if(isset($data->edate_type) && $data->edate_type)
        {
            $INS->edate_type  = $data->edate_type;
        }

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 證照
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_license::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //分類
        if(isset($data->license_type) && is_numeric($data->license_type) && $data->license_type !==  $UPD->license_type)
        {
            $isUp++;
            $UPD->license_type = $data->license_type;
        }
        //
        if(isset($data->license_issuing_kind) && is_numeric($data->license_issuing_kind) && $data->license_issuing_kind !==  $UPD->license_issuing_kind)
        {
            $isUp++;
            $UPD->license_issuing_kind = $data->license_issuing_kind;
        }
        //
        if(isset($data->edate_type) && strlen($data->edate_type) && $data->edate_type !==  $UPD->edate_type)
        {
            $isUp++;
            $UPD->edate_type = $data->edate_type;
        }
        //顯示名稱：證號
        if(isset($data->license_show_name1) && strlen($data->license_show_name1) && $data->license_show_name1 !==  $UPD->license_show_name1)
        {
            $isUp++;
            $UPD->license_show_name1 = $data->license_show_name1;
        }
        //顯示名稱：有效期限
        if(isset($data->license_show_name2) && strlen($data->license_show_name2) && $data->license_show_name2 !==  $UPD->license_show_name2)
        {
            $isUp++;
            $UPD->license_show_name2 = $data->license_show_name2;
        }
        //下載名稱1
        if(isset($data->license_show_name3) && strlen($data->license_show_name3) && $data->license_show_name3 !==  $UPD->license_show_name3)
        {
            $isUp++;
            $UPD->license_show_name3 = $data->license_show_name3;
        }
        //下載名稱2
        if(isset($data->license_show_name4) && $data->license_show_name4 !==  $UPD->license_show_name4)
        {
            $isUp++;
            $UPD->license_show_name4 = $data->license_show_name4;
        }
        //下載名稱3
        if(isset($data->license_show_name5) && $data->license_show_name5 !==  $UPD->license_show_name5)
        {
            $isUp++;
            $UPD->license_show_name5 = $data->license_show_name5;
        }
        //下載名稱3
        if(isset($data->license_issuing_kind3) && $data->license_issuing_kind3 !==  $UPD->license_issuing_kind3)
        {
            $isUp++;
            $UPD->license_issuing_kind3 = $data->license_issuing_kind3;
        }
        //下載名稱3
        if(isset($data->license_issuing_kind4) && $data->license_issuing_kind4 !==  $UPD->license_issuing_kind4)
        {
            $isUp++;
            $UPD->license_issuing_kind4 = $data->license_issuing_kind4;
        }
        //下載名稱3
        if(isset($data->license_issuing_kind5) && $data->license_issuing_kind5 !==  $UPD->license_issuing_kind5)
        {
            $isUp++;
            $UPD->license_issuing_kind5 = $data->license_issuing_kind5;
        }
        //發證有效年
        if(isset($data->edate_limit_year1) && ($data->edate_limit_year1) && $data->edate_limit_year1 !==  $UPD->edate_limit_year1)
        {
            $isUp++;
            $UPD->edate_limit_year1 = $data->edate_limit_year1;
        }
        //回訓有效年
        if(isset($data->edate_limit_year2) && ($data->edate_limit_year2) && $data->edate_limit_year2 !==  $UPD->edate_limit_year2)
        {
            $isUp++;
            $UPD->edate_limit_year2 = $data->edate_limit_year2;
        }
        //開始日期
        if(isset($data->sdate) && CheckLib::isDate($data->sdate) && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //結束日期
        if(isset($data->edate) && CheckLib::isDate($data->edate) && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
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
     * 取得 證照
     *
     * @return array
     */
    public function getApiLicenseList()
    {
        $ret = array();
        $typeAry = e_license_type::getSelect();
        $typeAry2= SHCSLib::getCode('LICENSE_ISSUING_KIND');
        $typeAry3= SHCSLib::getCode('LICENSE_ISSUING_FILE_KIND');
        //取第一層
        $data = e_license::orderby('isClose')->orderby('id')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['type']                       = isset($typeAry[$v->license_type])? $typeAry[$v->license_type] : '';
                $data[$k]['license_issuing_kind_name']  = isset($typeAry2[$v->license_issuing_kind])? $typeAry2[$v->license_issuing_kind] : '';
                $data[$k]['license_issuing_kind_name3'] = isset($typeAry3[$v->license_issuing_kind3])? $typeAry3[$v->license_issuing_kind3] : '';
                $data[$k]['license_issuing_kind_name4'] = isset($typeAry3[$v->license_issuing_kind4])? $typeAry3[$v->license_issuing_kind4] : '';
                $data[$k]['license_issuing_kind_name5'] = isset($typeAry3[$v->license_issuing_kind5])? $typeAry3[$v->license_issuing_kind5] : '';
                $data[$k]['close_user']                 = User::getName($v->close_user);
                $data[$k]['new_user']                   = User::getName($v->new_user);
                $data[$k]['mod_user']                   = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
