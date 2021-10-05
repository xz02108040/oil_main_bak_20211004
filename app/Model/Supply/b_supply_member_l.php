<?php

namespace App\Model\Supply;

use Lang;
use App\Model\User;
use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;

class b_supply_member_l extends Model
{
    use SupplyMemberLicenseTrait;

    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_member_l';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($supply_id, $uid, $license_id, $exid = 0)
    {
        if(!$uid || !$license_id) return 0;
        $data = b_supply_member_l::where('b_supply_id',$supply_id)->where('b_cust_id',$uid)->where('e_license_id',$license_id)->where('isClose','N');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

    //是否存在
    protected  function getLicense($uid, $license_id)
    {
        if(!$uid || !$license_id) return '';
        $data = b_supply_member_l::where('b_cust_id',$uid)->where('e_license_id',$license_id)->
                where('isClose','N')->select('license_code')->first();

        return isset($data->license_code)? $data->license_code : '';
    }
    //是否存在
    protected  function getLicenseInfo($id)
    {
        $ret = '';
        if(!$id) return $ret;
        $edateTypeAry = SHCSLib::getCode('LICENSE_ISSUING_KIND2');

        $data = b_supply_member_l::join('e_license as e','e.id','=','b_supply_member_l.e_license_id')->
        where('b_supply_member_l.id',$id)->select('b_supply_member_l.id','b_supply_member_l.e_license_id','e.name','b_supply_member_l.license_code',
            'b_supply_member_l.edate_type','b_supply_member_l.sdate','b_supply_member_l.edate')->first();
        if(isset($data->id))
        {
            $type = isset($edateTypeAry[$data->edate_type])? $edateTypeAry[$data->edate_type] : '';
            $ret  = $data->name.'('.$data->license_code.'，'.$type.'，'.$data->sdate.' ~ '.$data->edate.')';

        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($supply_id,$b_cust_id = 0,$isFirst = 1,$extAry = [])
    {
        $ret  = [];
        $edateTypeAry = SHCSLib::getCode('LICENSE_ISSUING_KIND2');
        $data = b_supply_member_l::
        join('e_license as e','e.id','=','b_supply_member_l.e_license_id')->
        where('b_supply_id',$supply_id)->
        select('b_supply_member_l.id','b_supply_member_l.e_license_id','e.name','b_supply_member_l.license_code',
            'b_supply_member_l.edate_type','b_supply_member_l.sdate','b_supply_member_l.edate')->where('b_supply_member_l.isClose','N');
        if($b_cust_id)
        {
            $data = $data->where('b_supply_member_l.b_cust_id',$b_cust_id);
        }

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                if(!in_array($val->e_license_id,$extAry))
                {
                    $type = isset($edateTypeAry[$val->edate_type])? $edateTypeAry[$val->edate_type] : '';
                    $ret[$val->id] = $val->name.'('.$val->license_code.'，'.$type.'，'.$val->sdate.' ~ '.$val->edate.')';
                }
            }
        }


        return $ret;
    }

    //取得 檔案
    protected  function getFile($id,$code = 'A')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_supply_member_l::find($id);
        if(isset($data->id))
        {
            if($code == 'C')
            {
                $ret = $data->file3;
            }
            elseif($code == 'B')
            {
                $ret = $data->file2;
            }
            else {
                $ret = $data->file1;
            }
        }
        return $ret;
    }

      /**
     *  停用該人員勞保.健保.團保,健康檢查 
     * @param $id
     * @return int
     */
    protected function closeSupplyMemberLicense($b_cust_id ,$mod_user = 1)
    {
        if (!$b_cust_id) return 0;
        $now = date('Y-m-d H:i:s');
        $LicenseAry  = SHCSLib::getCode('CLOSE_SUPPLY_MEMBER_LICENSE');
        list($keys, $values) = array_divide($LicenseAry);

        $data  = b_supply_member_l::where('b_cust_id', $b_cust_id)
        ->whereIn('e_license_id', $keys)
        ->where('isClose', 'N')->get();

        foreach ($data as $val) {
            $upAry = array();
            $upAry['id']            = $val->id;
            $upAry['isClose']       = 'Y';
            $UPD = $this->setSupplyMemberLicense($val->id, $upAry, $mod_user);
        }
        return $data->count();
    }
}
