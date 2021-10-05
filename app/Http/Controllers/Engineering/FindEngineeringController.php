<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
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
use App\Model\Engineering\e_project_car;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_s;
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_time;
use App\Model\Factory\b_car;
use App\Model\User;
use App\Model\WorkPermit\wp_work;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class FindEngineeringController extends Controller
{
    use EngineeringMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | FindEngineeringController
    |--------------------------------------------------------------------------
    |
    | 查詢工程案件 相關資料
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
        $this->hrefMain         = 'findEngineering';
        $this->langText         = 'sys_emp';


    }
    /**
     * 搜尋工程案件 相關資料
     *
     * @return void
     */
    public function findEngineering(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data   = [];
        $type   = $request->has('type')? $request->type : 0;
        $sid    = is_numeric($request->sid) ? $request->sid : 0;//承攬商
        $eid    = is_numeric($request->eid) ? $request->eid : 0;//工程案件
        $uid    = is_numeric($request->uid) ? $request->uid : 0;//使用者
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 5)
            {
                //搜尋：工作許可証
                $data = wp_work::getSelect($eid);
            }elseif($type == 4)
            {
                //搜尋：工程案件之車輛
                $data = e_project_car::getSelect($eid);
            }elseif($type == 3)
            {
                //搜尋：工程案件之轄區監造
                $data = e_project_d::getSelect($eid);
            }elseif($type == 2)
            {
                //搜尋：工程案件場地
                $data = e_project_f::getSelect($eid);
            }elseif($type == 1)
            {
                //搜尋：工程案件成員
                $data = e_project_s::getSelect($eid);
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }

    /**
     * 搜尋教育訓練 相關資料
     *
     * @return void
     */
    public function findCourse(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $data   = [];
        $type   = $request->has('type')? $request->type : 0;
        $cid    = is_numeric($request->cid) ? $request->cid : 0;//教育訓練課程
        //-------------------------------------------//
        // 資料
        //-------------------------------------------//
        if($type)
        {
            if($type == 2)
            {
                //搜尋：開課課堂
                $data = et_traning_time::getSelect(1);
            }
            elseif($type == 1)
            {
                //搜尋：開課課堂
                $data = et_traning::getSelect($cid);
            }
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return response()->json($data);
    }
}
