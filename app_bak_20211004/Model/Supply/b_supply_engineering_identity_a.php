<?php

namespace App\Model\Supply;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_engineering_identity_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_engineering_identity_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($iid,$eid,$extid = 0)
    {
        if(!$iid || !$eid) return 0;
        $data = b_supply_engineering_identity_a::where('b_supply_engineering_identity_id',$iid);
        $data = $data->where('e_license_id',$eid)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    /**
     * 取得 所需之證照名稱
     */
    protected function getAllLicense($iid)
    {
        $ret  = '';
        if(!$iid) return $ret;
        $data = b_supply_engineering_identity_a::where('b_supply_engineering_identity_id',$iid)->where('isClose','N')->
                select('e_license_id')->get();
        foreach ($data as $val)
        {
            if($ret) $ret .= '<br/>';
            $ret .= e_license::getName($val->e_license_id);
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($iid,$isFirst = 1, $extAry = [])
    {
        $ret    = [];
        $data   = b_supply_engineering_identity_a::where('b_supply_engineering_identity_id',$iid)->
                    select('id','e_license_id')->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('e_license_id',$extAry);
        }
        $data   = $data->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->e_license_id] = e_license::getName($val->e_license_id);
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApplySelect($iid, $extAry = [], $isApply = '')
    {
        $ret    = [];
        $nameStr= '';//○X
        $data   = b_supply_engineering_identity_a::join('e_license as l','l.id','=','e_license_id')->
        where('b_supply_engineering_identity_id',$iid)->
        select('l.*')->where('b_supply_engineering_identity_a.isClose','N')->where('l.isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('e_license_id',$extAry);
        }
        $data   = $data->get();

        foreach ($data as $key => $val)
        {
            if(strlen($nameStr)) $nameStr .= '/';
            $color = ($isApply == 'Y')? 'blue' : 'red';
            $nameStr .= HtmlLib::Color('Ｘ'.$val->name,$color);

            $tmp = [];
            $tmp['license_id']   = $val->id;
            $tmp['license_name'] = $val->name;
            $tmp['license_code'] = $val->license_code;
            $tmp['license_type'] = $val->license_type;
            $tmp['edate']        = '';
            $tmp['file1']        = '';
            $tmp['file2']        = '';
            $tmp['file3']        = '';
            $tmp['filePath1']    = '';
            $tmp['filePath2']    = '';
            $tmp['filePath3']    = '';
            $tmp['fileImg1']     = '';
            $tmp['fileImg2']     = '';
            $tmp['fileImg3']     = '';
            $tmp['isOk']         = 'N';
            $ret[$val->id] = $tmp;
        }

        return [$nameStr,$ret];
    }
}
