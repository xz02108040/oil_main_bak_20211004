<?php

namespace App\Http\Controllers;


use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\TokenLib;
use Session;
use Lang;
use Auth;

class PrivacyController extends Controller
{
    use SessTraits,BcustTrait;
    /*
    |--------------------------------------------------------------------------
    | PrivacyController
    |--------------------------------------------------------------------------
    |
    | APP 專用隱私權
    |
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
        $this->hrefHome      = '/Privacy';

        $this->pageTitleN    = Lang::get('sys_base.base_10201');//標題
        //個人佈告欄
        $this->personLoginSuc    = Lang::get('sys_base.base_10211');//標題
        $this->personLoginErr    = Lang::get('sys_base.base_10212');//標題
        $this->personSignErr     = Lang::get('sys_base.base_10213');//標題
        $this->personSignErr2    = Lang::get('sys_base.base_10214');//標題

    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index()
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        //-------------------------------------------//
        //View
        //-------------------------------------------//

        $content = Lang::get('sys_base.privacy_web');

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleN,'content'=>$content,'menu'=>$this->sys_menu];

        return view('report',$retArray);
    }
}
