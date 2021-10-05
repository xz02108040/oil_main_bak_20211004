<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptPermit2Trait;
use App\Http\Traits\Report\ReptPermitTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Report\rept_doorinout_t;
use App\Model\WorkPermit\wp_work;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindReptPermitController extends Controller
{
    use SessTraits;
    use ReptPermitTrait,ReptPermit2Trait;
    /*
    |--------------------------------------------------------------------------
    | FindReptPermitController
    |--------------------------------------------------------------------------
    |
    | 查詢 工作許可證當日紀錄 相關資料
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
        $this->hrefMain         = 'findPermitRept1';
        $this->langText         = 'sys_rept';


    }

    /**
     * 取得 該廠區 當日進出儀表板 「主頁」
     * findRept1
     * @return void
     */
    public function findPermitRept1(Request $request)
    {
        //讀取 Session 參數
        //參數
        $type   = $request->t ? SHCSLib::decode($request->t) : '';
        $local  = $request->l ? $request->l : '';
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
//        dd($local,$b_factory_a_id,$store);
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($store)
        {
            $html = $this->genPermitWorkHtml($store,$type);
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }
    /**
     * 取得 該廠區 當日進出儀表板 「主頁」
     * findRept1
     * @return void
     */
    public function findPermitRept2(Request $request)
    {
        //讀取 Session 參數
        //參數
        $type   = $request->t ? SHCSLib::decode($request->t) : '';
        $local  = $request->l ? $request->l : '';
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
//        dd($local,$b_factory_a_id,$store);

        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($store)
        {
            $html = $this->genRermit2Html($store);
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }
    /**
     * 取得 該廠區 當日進出儀表板 「主頁」
     * findRept1
     * @return void
     */
    public function findPermitRept2A(Request $request)
    {
        //讀取 Session 參數
        //參數
        $type   = $request->t ? SHCSLib::decode($request->t) : '';
        $local  = $request->l ? $request->l : '';
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
//        dd($local,$b_factory_a_id,$store);
        $today  = date('Y-m-d');
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($store)
        {
            $html = wp_work::getTodayCount($today,$store);
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }
    /**
     * 取得 目前廠區內人數
     * findRept1
     * @return void
     */
    public function findPermitRept2B(Request $request)
    {
        //讀取 Session 參數
        //參數
        $type   = $request->t ? SHCSLib::decode($request->t) : '';
        $local  = $request->l ? $request->l : '';
        $b_factory_a_id = b_factory_b::isIDCodeExist($local);
        $store  = ($b_factory_a_id)? b_factory_a::getStoreId($b_factory_a_id) : 2;
//        dd($local,$b_factory_a_id,$store);
        $today  = date('Y-m-d');
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($store)
        {
            $html = rept_doorinout_t::getMenCount($today,$store);
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return $html;
    }
}
