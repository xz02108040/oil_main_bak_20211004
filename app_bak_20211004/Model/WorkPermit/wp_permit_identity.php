<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project_l;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_identity extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_identity';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否 工作許可證設計的 工程身份存在
    protected  function isExist($wid,$iid)
    {
        if(!$wid || !$iid) return 0;
        $data = wp_permit_identity::where('wp_permit_id',$wid)->where('engineering_identity_id',$iid)->
        where('isClose','N')->first();

        return (isset($data->id))? $data->id : 0;
    }

    //取得 工程身份 名稱
    protected  function getName($wid,$iid)
    {
        if(!$wid || !$iid) return '';
        $data = wp_permit_identity::where('wp_permit_id',$wid)->where('engineering_identity_id',$iid)->
        where('isClose','N')->first();

        return (isset($data->id))? b_supply_engineering_identity::getName($data->engineering_identity_id) : '';
    }

    //取得 工程身份 名稱
    protected  function getData($wid,$iid)
    {
        if(!$wid || !$iid) return '';
        $data = wp_permit_identity::where('wp_permit_id',$wid)->where('engineering_identity_id',$iid)->
        where('isClose','N')->first();

        return (isset($data->id))? b_supply_engineering_identity::getName($data->engineering_identity_id) : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($wp_permit_id , $isFirst = 1 , $extAry = [])
    {
        $ret    = [];
        if(!$wp_permit_id) return $ret;
        $idAry  = b_supply_engineering_identity::getSelect(0);

        $data   = wp_permit_identity::where('wp_permit_id',$wp_permit_id)->select('engineering_identity_id')->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('engineering_identity_id',$extAry);
        }
        $data   = $data->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->engineering_identity_id] = isset($idAry[$val->engineering_identity_id])? $idAry[$val->engineering_identity_id] : '';
        }

        return $ret;
    }

    /**
     * 取得 工作許可證種類 允許之工程身份
     * @param $wid
     * @param $sid
     * @param int $isFirst
     * @param array $extAry
     * @return array
     */
    protected  function getSupplyIdentitySelect($permit_id , $supply_id = 0, $project_id = 0 , $extAry = [], $isFirst = 1)
    {
        $ret    = [];
        $ret[0] = Lang::get('sys_base.base_10015');
        if(!$permit_id) return $ret;
        //取得 工作許可證種類支援的工程身份
        // $idAry       = wp_permit_identity::getSelect($permit_id,$isFirst,$extAry); // 目前大林專案未提供工作許可證允許的工程身分編輯介面，導致無法選到新增的工程身分，故註解此限制，僅需以承攬商案件成員擁有工程身分為主
        //取得 承攬商 擁有工程身份之成員名單<該承攬商，該工程案件>
        $supplyidAry = e_project_l::getSelect($project_id,1,1);
        //dd([$idAry,$supplyidAry,$extAry]);
        // if(count($idAry))
        // {
        //     foreach ($idAry as $id => $val)
        //     {
        //         if($id && !isset($supplyidAry[$id]))
        //         {
        //             unset($idAry[$id]);
        //         }
        //     }
        //     $ret = $idAry;
        // }
        return $supplyidAry;
    }
}
