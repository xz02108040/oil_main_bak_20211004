<?php

namespace App\Http\Traits\Bcust;

use App\Lib\CheckLib;
use App\Model\b_menu_group;
use App\Model\bc_type_app;
use App\Model\Bcust\b_cust_a;
use App\Model\sys_param;
use Storage;
use App\Lib\SHCSLib;

/**
 * 個人資訊.
 * User: dorado
 *
 */
trait BcustATrait
{
    /**
     * 新增 使用者 個人資訊
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBcustA($data,$mod_user = 1)
    {
        $ret = false;
        $b_cust_id = 0;
        if(!isset($data->b_cust_id)) return $ret;
        $sexAry         = array_keys(SHCSLib::getCode('SEX'));
        $bloodAry       = array_keys(SHCSLib::getCode('BLOOD'));
        $bloodrhAry     = array_keys(SHCSLib::getCode('BLOODRH'));
        $kindAry        = array_keys(SHCSLib::getCode('PERSON_KIND'));
        $haedImg        = isset($data->head_img)? $data->head_img : '';
        if(isset($data->head_img_data) && isset($data->head_img_ext))
        {
            //人頭像比例
            $head_max_height = sys_param::getParam('USER_HEAD_HEIGHT',640);
            $head_max_width  = sys_param::getParam('SER_HEAD_WIDTH',360);
            //圖片位置
            $filepath = config('mycfg.user_head_path').date('Y/').$data->b_cust_id.'/';
            $filename = $data->b_cust_id.'_head.'.$data->head_img_ext;
            //轉換 圖片大小
            if(SHCSLib::tranImgSize($filepath.$filename,$data->head_img_data,$head_max_width,$head_max_height))
            {
                $haedImg = $filepath.$filename;
            }
        }


        $INS = new b_cust_a();
        $INS->b_cust_id         = $data->b_cust_id;
        $INS->head_img          = strlen($haedImg)? $haedImg : (isset($data->head_img_path)? $data->head_img_path : '');
        $INS->head_img_at       = strlen($haedImg)? time() : 0;
        $INS->sex               = (isset($data->sex) && in_array($data->sex,$sexAry))? $data->sex : 'N';
        $INS->blood             = (isset($data->blood) && in_array($data->blood,$bloodAry))? $data->blood : '';
        $INS->bloodRh           = (isset($data->bloodRh) && in_array($data->bloodRh,$bloodrhAry))? $data->bloodRh : '';
        $INS->bc_id             = isset($data->bc_id)? strtoupper($data->bc_id) : '';
        $INS->birth             = isset($data->birth)? $data->birth : '1970-01-01';
        $INS->tel1              = isset($data->tel1)? $data->tel1 : '';
        $INS->mobile1           = isset($data->mobile1)? $data->mobile1 : '';
        $INS->email1            = isset($data->email1)? $data->email1 : '';
        $INS->addr1             = isset($data->addr1)? $data->addr1 : '';
        $INS->kin_kind          = (isset($data->kin_kind) && in_array($data->kin_kind,$kindAry))? $data->kin_kind : 0;
        $INS->kin_tel           = isset($data->kin_tel)? $data->kin_tel : '';
        $INS->kin_user          = isset($data->kin_user)? $data->kin_user : '';
        $INS->mod_user          = $mod_user;

        //如果新增成功
        if($INS->save())
        {
            $isUp      = 0;
            $b_cust_id = $INS->b_cust_id;
        }

        return $b_cust_id;
    }

    /**
     * 修改 個人資訊
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBcustA($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $sexAry         = array_keys(SHCSLib::getCode('SEX'));
        $bloodAry       = array_keys(SHCSLib::getCode('BLOOD'));
        $bloodrhAry     = array_keys(SHCSLib::getCode('BLOODRH'));
        $kindAry        = array_keys(SHCSLib::getCode('PERSON_KIND'));
        $isUp = 0;

        $UPD = b_cust_a::find($id);
        //
        if(isset($data->head_img) && strlen($data->head_img))
        {
            $isUp++;
            $UPD->head_img = $data->head_img;
            $UPD->head_img_at = time();
        }
        //
        if(isset($data->sex) && in_array($data->sex,$sexAry) && $data->sex !== $UPD->sex)
        {
            $isUp++;
            $UPD->sex = $data->sex;
        }
        if(isset($data->blood) && in_array($data->blood,$bloodAry) && $data->blood !== $UPD->blood)
        {
            $isUp++;
            $UPD->blood = $data->blood;
        }
        if(isset($data->bloodRh) && in_array($data->bloodRh,$bloodrhAry) && $data->bloodRh !== $UPD->bloodRh)
        {
            $isUp++;
            $UPD->bloodRh = $data->bloodRh;
        }
        if(isset($data->bc_id) && $data->bc_id && $data->bc_id !== $UPD->bc_id)
        {
            $isUp++;
            $UPD->bc_id = strtoupper($data->bc_id);
        }
        if(isset($data->birth) && CheckLib::isDate($data->birth) && $data->birth !== $UPD->birth)
        {
            $isUp++;
            $UPD->birth = $data->birth;
        }
        if(isset($data->tel1) && $data->tel1 !== $UPD->tel1)
        {
            $isUp++;
            $UPD->tel1 = $data->tel1;
        }
        if(isset($data->mobile1) && $data->mobile1 !== $UPD->mobile1)
        {
            $isUp++;
            $UPD->mobile1 = $data->mobile1;
        }
        if(isset($data->email1) && $data->email1 !== $UPD->email1)
        {
            $isUp++;
            $UPD->email1 = $data->email1;
        }
        if(isset($data->addr1) && $data->addr1 !== $UPD->addr1)
        {
            $isUp++;
            $UPD->addr1 = $data->addr1;
        }
        if(isset($data->kin_kind) && in_array($data->kin_kind,$kindAry) && $data->kin_kind !== $UPD->kin_kind)
        {
            $isUp++;
            $UPD->kin_kind = $data->kin_kind;
        }
        if(isset($data->kin_user) && $data->kin_user !== $UPD->kin_user)
        {
            $isUp++;
            $UPD->kin_user = $data->kin_user;
        }
        if(isset($data->kin_tel) && $data->kin_tel !== $UPD->kin_tel)
        {
            $isUp++;
            $UPD->kin_tel = $data->kin_tel;
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

}
