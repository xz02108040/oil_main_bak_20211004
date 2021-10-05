<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorLogTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindDoorController extends Controller
{
    use ReptDoorFactoryTrait,ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait,SessTraits;
    use ReptDoorLogTrait;
    /*
    |--------------------------------------------------------------------------
    | FindDoorController
    |--------------------------------------------------------------------------
    |
    | 查詢 門禁進出 相關資料
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
        //$this->middleware('auth');
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'findRept1';
        $this->langText         = 'sys_rept';


    }

    /**
     * 取得 該廠區 當日進出儀表板 「主頁」
     * findRept1
     * @return void
     */
    public function findDoorInOutTodayFactory(Request $request)
    {
        //讀取 Session 參數
        //參數
        $today  = $request->has('t')? SHCSLib::decode($request->t) : date('Y-m-d');
        $store  = $request->has('s')? SHCSLib::decode($request->s) : 0;
        $kind   = $request->has('k')? SHCSLib::decode($request->k) : 1;
        $local  = $request->has('l')? SHCSLib::decode($request->l) : 0;
        $size   = Session::get('rept_doorinout_t.view',1);
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($today)
        {
            if($kind == 2)
            {
                $html = $this->genDoorLogHtml($local);
            } else {
                $html = $this->genDoorInOutFactoryHtml('M',$store,$today,$size);
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 取得 該廠區人員 當日進出儀表板 「承商(人員)」「承商(車輛)」
     * findRept2
     * @return void
     */
    public function findDoorInOutToday(Request $request)
    {
        //參數
        $html       = '';
        $sid        = $request->has('sid')? SHCSLib::decode($request->sid) : 0;
        $cmp        = $request->has('cmp')? SHCSLib::decode($request->cmp) : -1;
        $local      = Session::get('rept_doorinout_t.local',0);
        $localUrl   = ($local)? '&local='.$local : '';
        $type       = $request->has('type')?  $request->type : 'M';
        $level      = $request->has('level') ? $request->level : 1;
        //dd([$sid,$local,$localUrl,$type,$level]);
        $html = $request->has('sid')? SHCSLib::decode($request->sid) : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($sid > 0)
        {
            if($type == 'C')
            {
                $html = $this->genDoorInOutTodayCarHtml($level,$sid, $cmp,$localUrl);
            } else {
                $html = $this->genDoorInOutTodayMenHtml($level,$sid, $cmp,$localUrl);
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }

    /**
     * 取得 總廠 總人數
     *
     * @return void
     */
    public function findFactoryTotalMenInOutAmt(Request $request)
    {
        $today      = date('Y-m-d');
        if($request->has('l'))
        {
            $store_id = b_factory_d::getStoreId(SHCSLib::decode($request->l));
            $storeAry = [$store_id];
        } else {
            $storeAry   = sys_param::getParam('REPORT_DEFAULT_STORE',1);
            $storeAry   = explode(',',$storeAry);
        }
        return rept_doorinout_t::getMenCount($today,$storeAry);
    }

    /**
     * 取得 總廠 總車輛數
     *
     * @return void
     */
    public function findFactoryTotalCarInOutAmt(Request $request)
    {
        $today      = date('Y-m-d');
        if($request->has('l'))
        {
            $store_id = b_factory_d::getStoreId(SHCSLib::decode($request->l));
            $storeAry = [$store_id];
        } else {
            $storeAry   = sys_param::getParam('REPORT_DEFAULT_STORE',1);
            $storeAry   = explode(',',$storeAry);
        }
        return rept_doorinout_car_t::getCarCount($today,$storeAry);
    }

    /**
     * 取得
     *
     * @return void
     */
    public function findDoorImg(Request $request)
    {
        $today      = date('Y-m-d');




        return rept_doorinout_car_t::getCarCount($today,$storeAry);
    }
}
