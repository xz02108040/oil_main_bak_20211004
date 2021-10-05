<?php

namespace App\Http\Controllers\Auth;

use Lang;
use Session;
use App\Lib\LogLib;
use App\Lib\TokenLib;
use App\Model\sys_param;
use App\Model\Emp\b_cust_e;
use Illuminate\Http\Request;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\MenuTraits;
use App\Model\Factory\b_factory;
use App\Http\Traits\MenuAuthTrait;
use App\Http\Controllers\Controller;
use App\Model\View\view_dept_member;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\SessUpdateTraits;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | 帳號登入
    |
    */

    use AuthenticatesUsers,MenuTraits,MenuAuthTrait,SessUpdateTraits,BcustTrait;

    /**
     * 參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct()
    {
        //$this->middleware('guest')->except('logout');
    }

    /**
     * 顯示 登入頁面
     */
    protected function showLogin()
    {
        $storeSelect = \Form::select('store_id',b_factory::getSelect(),0,['id'=>'store_id','class'=>'form-control ']);

        // 依據伺服器位置，抓取對應的系統版本號
        if (preg_match("/dooradmin_oil1/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('OIL1_VERSION',0);
        } elseif (preg_match("/dooradmin_oil2/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('OIL2_VERSION',0);
        } elseif (preg_match("/dooradmin_cpc1/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('CPC1_VERSION',0);
        } elseif (preg_match("/dooradmin_cpc2/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('CPC2_VERSION',0);
        } elseif (preg_match("/dooradmin_httc1/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('HTTC1_VERSION',0);
        } elseif (preg_match("/dooradmin_httc2/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = sys_param::getParam('HTTC2_VERSION',0);
        } elseif (preg_match("/dooradmin_cpc_main/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = Lang::get('sys_comp.COMP_ADMIN');
        } elseif (preg_match("/dooradmin_cpc_supply/i", $_SERVER['REDIRECT_URL'])) {
            $System_Version = Lang::get('sys_comp.COMP_ADMIN');
        } else {
            $System_Version = date('Y-m-d');
        }

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        return view('auth.login', ['storeSelect' => $storeSelect, 'System_Version' => $System_Version]);
    }

    /**
     * 登入判斷
     * @param Request $request
     */
    public function login(Request $request)
    {
        //session 清空
        Session::flush();
        //登入帳號密碼
        $login      = ($request->has('account'))?    trim($request->account)       : '';
        $pwd        = ($request->has('password'))?   $request->password      : '';
        $store      = ($request->has('store_id'))?   $request->store_id      : 0;
        $remember   = $request->remember;
        $msg        = Lang::get('sys_base.base_10100'); //登入失敗

        //1. 是否有帳密
        if (Auth::attempt(array('account' => $login, 'password' => $pwd, 'isClose'=>'N','isLogin'=>'Y'),$remember))
        {
            //1-1. 帳號身分
            $bctype = Auth::user()->bc_type;
            $this->user_id = Auth::id();
            //如果帳號身分 不是 自建/職員
            if(!in_array($bctype,[1,2]))
            {
                //登入失敗:身分不對
                return redirect('/login')->withErrors([
                    'errors' => Lang::get('sys_base.base_10116'),
                ]);
            }
            //如果是 身分為自建，都只能到 總部
            if($bctype == 1) $store = 1;
            $this->token =  $this->apiKey = '';

            //3.3 本次登入SISSON
            $session_id = Session::getId();
            //3.3.1 如果SESSION 與上次不同
            if(Auth::user()->last_session != $session_id)
            {
                //更換session
                $upAry['last_session'] = $session_id;
                //Token失效
                TokenLib::closeToken($this->user_id,'web');
            }
            //3.3.9 如果有要更新[會員帳號資料]
            if(count($upAry))
            {
                $this->setBcust($this->user_id,$upAry,$this->user_id);
            }
            //3.9 Token
            $getTokenRet = TokenLib::getToken('web',$this->user_id);
            //如果正確取得Token
            if($getTokenRet['ret'] == 'Y') {
                $this->token    = $getTokenRet['token'];    //帳號TOKEN
                $this->apiKey   = $getTokenRet['apiKey'];   //圖片ＡＰＩＫＥＹ
            }
            Session::put('user.token',$this->token);
            Session::put('user.apiKey',$this->apiKey);

            //1-2. 職員身分
            if($bctype == 2)
            {
                //檢查 職員身分是否存在
                $bcuste = b_cust_e::where('b_cust_id',Auth::id())->where('isVacate','N')->
                where('be_edate','>=',date('Y-m-d'))->count();

                if(!$bcuste)
                {
                    //登入失敗：非職員 或是已離職停權
                    return redirect('/login')->withErrors([
                        'errors' => Lang::get('sys_base.base_10117'),
                    ]);
                } else {
                    $store = view_dept_member::getStore(Auth::id());
                }
            }
            //廠區權限辨識 Ａ：總部，Ｂ：廠區
            $sys_kind = ( $store == 1)? 'A' : 'B';
            //系統標題
            Session::put('user.store_name',Lang::get('sys_base.base_title'));
            //帳號個人資訊
            $this->updateBCustSess();
            //選單群組
            Session::put('user.b_menu_group_id',Auth::user()->b_menu_group_id);
            //MENU
            Session::put('user.sys_menu',$this->getApiMenu(Auth::user()->b_menu_group_id,'A',$this->token));//$sys_kind
            Session::put('user.menu_auth',$this->getApiMenuAuthRui(Auth::user()->b_menu_group_id));
            Session::put('user.menu_wirte',$this->getApiMenuAuthWrite(Auth::user()->b_menu_group_id));
            //「廠區系統辨識」
            Session::put('user.sys_kind', $sys_kind);
            Session::put('user.bc_type' ,$bctype);
            Session::put('user.store_id',$store);
            //系統參數
            //Session::put('user.sys_code',$ret->sys_code);

            //Log
            LogLib::putLoginLog(1,$login,$request->ip(),'',1);
            return redirect('/');
        } else {
            //Log
            LogLib::putLoginLog(0,$login,$request->ip(),'',1);
            //登入失敗
            return redirect('/login')->withErrors([
                'errors' => $msg,
            ]);
        }
    }

    /**
     * 登出請求.
     *
     * @return Response
     */
    public function logout()
    {
        //session 清空
        Session::flush();
        //登出
        Auth::logout();

        return redirect('/login');
    }
}
