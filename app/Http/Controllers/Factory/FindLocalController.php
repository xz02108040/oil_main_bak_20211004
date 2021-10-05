<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_dept_a;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindLocalController extends Controller
{
    use EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FindLocalController
    |--------------------------------------------------------------------------
    |
    | 查詢場地 相關資料
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
        $this->hrefMain         = 'findLocal';
        $this->langText         = 'sys_factory';


    }
    /**
     * 搜尋廠區相關
     *
     * @return void
     */
    public function findLocal(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data   = [];
        $type   = $request->has('type')? $request->type : 0;
        $fid    = is_numeric($request->fid) ? $request->fid : 0;
        $kid    = is_numeric($request->kid) ? $request->kid : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 2)
            {
                //搜尋：場地設備
                $data = b_factory_b::getSelect(0,$fid);
            }elseif($type == 1)
            {
                //搜尋：廠區場地
                $data = b_factory_a::getSelect($fid,$kid);
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }


}
