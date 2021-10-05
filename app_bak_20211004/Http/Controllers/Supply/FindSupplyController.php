<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_used_rfid;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindSupplyController extends Controller
{
    use SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FindSupplyController
    |--------------------------------------------------------------------------
    |
    | 查詢承攬商 相關資料
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'findContractor';
        $this->langText         = 'sys_supply';


    }
    /**
     * 搜尋 承攬商
     *
     * @return void
     */
    public function findSupply(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data       = $usedAry = [];
        $type       = $request->has('type')? $request->type : 0;
        $tid        = is_numeric($request->tid) ? $request->tid : 0;
        $sid        = is_numeric($request->sid) ? $request->sid : 0;
        $rfidtype   = is_numeric($request->rfidtype) ? $request->rfidtype : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($rfidtype)
            {
                $usedAry = view_used_rfid::getSelect($rfidtype);
                //移除正在使用中的 ＲＦＩＤ卡片
                if(count($usedAry))
                {
                    foreach ($usedAry as $val)
                    {
                        if(isset($data[$val]))
                        {
                            unset($data[$val]);
                        }
                    }
                }
            }
            //搜尋：承攬商車輛
            if($type == 2)
            {
                $data = b_car::getSelect(0,$sid);
            }
            //搜尋：分類->承攬商成員
            elseif($type == 1)
            {
                $data = b_supply_member::getSelect($sid,1);
            }
            //搜尋：分類->承攬商成員，去除已有卡片 且通過教育訓練的人
            elseif($type == 3)
            {
                $data[] = Lang::get('sys_base.base_10015');
                //1.搜尋：分類->承攬商成員
                $memberAry = view_door_supply_member::getCoursePassMember($sid);

                if(count($memberAry))
                {
                    //2. 取得卡片人員
                    $rfidAry = view_used_rfid::getSelect(5,$sid);
                    //dd([$memberAry,$rfidAry]);
                    foreach ($memberAry as $val)
                    {
                        if(!isset($rfidAry[$val['b_cust_id']]))
                        {
                            $data[$val['b_cust_id']] = $val['name'];
                        }
                    }
                }
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }

    /**
     * 搜尋 工程身份
     *
     * @return void
     */
    public function findSupplyIdentity(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data       = $usedAry = [];
        $type       = $request->has('type')? $request->type : 0;
        $tid        = is_numeric($request->tid) ? $request->tid : 0;
        $sid        = is_numeric($request->sid) ? $request->sid : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 1)
            {
                //搜尋：分類->工程身份
                $data = e_license::getSelect($tid,1);
            }
        }
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }

    /**
     * 搜尋承攬商之成員擁有的工程身份
     *
     * @return void
     */
    public function findSupplyRpAproc(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data = [];
        $type = $request->has('type') ? $request->type : 0;
        $kid  = is_numeric($request->kid) ? $request->kid : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 1)
            {
                //搜尋：
                $data   = SHCSLib::getCode('RP_DOOR_APROC1',1);
                if($kid == 1)
                {
                    unset($data['R']);
                } else {
                    unset($data['P']);
                }
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }
}
