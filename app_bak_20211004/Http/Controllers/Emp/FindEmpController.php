<?php

namespace App\Http\Controllers\Emp;

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
use App\Model\Factory\b_car;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindEmpController extends Controller
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
        $this->hrefMain         = 'findEmp';
        $this->langText         = 'sys_emp';


    }
    /**
     * 搜尋職員
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
        $sid    = is_numeric($request->sid) ? $request->sid : 0;//廠區
        $eid    = is_numeric($request->eid) ? $request->eid : 0;//部門
        $uid    = is_numeric($request->uid) ? $request->uid : 0;//使用者
        $level  = is_numeric($request->level) ? $request->level : 0;//使用者
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 2)
            {
                //搜尋：部門底下的職員
                $data = b_cust_e::getSelect($sid,$eid,0,$uid);
            }elseif($type == 3)
            {
                //搜尋：實體部門
                $data = be_dept::getSelect(0,$sid,$level,'Y',$eid,2);
            }elseif($type == 5)
            {
                //搜尋：所有部門
                $data = be_dept::getSelect(0,0,0,'',$eid);
            }elseif($type == 4)
            {
                //搜尋：部門車輛
                $data = b_car::getSelect($eid);
            }elseif($type == 1)
            {
                //搜尋：部門可用職稱
                $data = be_title::getSelect();
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }


}
