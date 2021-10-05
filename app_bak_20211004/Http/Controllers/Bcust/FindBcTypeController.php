<?php

namespace App\Http\Controllers\Bcust;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\bc_type_app;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_dept_a;
use App\Model\Emp\be_title;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindBcTypeController extends Controller
{
    use EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FindEmpController
    |--------------------------------------------------------------------------
    |
    | 查詢職員 相關資料
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
        $this->hrefMain         = 'findBcType';
        $this->langText         = 'sys_base';


    }
    /**
     * 搜尋 帳號身分/ＡＰＰ身分
     *
     * @return void
     */
    public function findEmp(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data   = [];
        $type   = $request->has('type')? $request->type : 0;
        $tid    = is_numeric($request->tid) ? $request->tid : 0;
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 2 && $tid)
            {
                //搜尋：ＡＰＰ身分
                $data = bc_type_app::getSelect($tid);
            }elseif($type == 1)
            {
                //搜尋：帳號身分
                $data = SHCSLib::getCode('BC_TYPE');
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }


}
