<?php
namespace App\Http\Traits;

use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\b_menu_group;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_e;
use App\Model\sys_param;
use Uuid;
use Session;
use Auth;
use Lang;
/**
 * 更新 Session內容.
 * User: dorado
 */
trait SessUpdateTraits {
    /**
     * 更新[會員]
     */
    protected  function updateBCustSess()
    {
        $bcuste   = [];
        $store_id = 1;
        $bc_type  = Auth::user()->bc_type;
        $BCTYPE   = SHCSLib::getCode('BC_TYPE');
        $bc_type_name = isset($BCTYPE[Auth::user()->bc_type])? $BCTYPE[$bc_type] : Lang::get('sys_base.base_10011');

        Session::put('user.b_cust_id',Auth::user()->id);
        Session::put('user.name',Auth::user()->name);
        Session::put('user.bc_type.id',Auth::user()->bc_type);
        Session::put('user.bc_type.name',$bc_type_name);
        Session::put('user.isRoot',Auth::user()->isRoot);
        //2019-08-06 電子簽名
        //電子簽圖檔
        $sign_img = Auth::user()->sign_img;
        $sign_url = ($sign_img)? url('/img/Sign/'.SHCSLib::encode(Auth::user()->id)) : '';
        Session::put('user.sign_img',storage_path('app'.$sign_img));
        Session::put('user.sign_url',$sign_url);

        //上次登入紀錄
        $login_log      = LogLib::getLastLoginLog(Auth::user()->account);
        $login_suc_at   = isset($login_log['suc']->created_at)? substr($login_log['suc']->created_at,0,16) : '';
        $login_err_at   = isset($login_log['err']->created_at)? substr($login_log['err']->created_at,0,16) : '';
        Session::put('user.login_suc_at',$login_suc_at);
        Session::put('user.login_err_at',$login_err_at);

        //顯示個人資訊用
        Session::put('user.user_title',$bc_type_name);
        Session::put('user.user_subtitle',Lang::get('sys_base.base_10012',['sdate'=>date('Y-m-d',strtotime(Auth::user()->created_at))]));
        //職員
        $be_dept    = '';
        $be_dept_id = $be_title_id = 0;
        $be_title   = '';
        if($bc_type == 2)
        {
            $bcuste   = b_cust_e::find(Auth::user()->id);
            $be_dept_id  = $bcuste->be_dept_id;
            $store_id    = $bcuste->b_factory_id;
            $be_title_id = $bcuste->be_title_id;
            $be_title    = be_title::getName($be_title_id);
            $be_dept     = be_dept::getName($be_dept_id);
            //$group    = b_menu_group::getName(Auth::user()->b_menu_group_id);
            Session::put('user.user_title',$be_dept);
            Session::put('user.user_subtitle',$be_title);

            //[轄區主簽者]
            $allowDeptAry   = ($be_title_id == 3)? be_dept::getLevelDeptAry($be_dept_id) : [];
            Session::put('user.allowDeptAry',$allowDeptAry);
            //允許管理的工程案件
            $allowProjectAry = e_project::getChargeAry(Auth::user()->id,$allowDeptAry);
            Session::put('user.allowProjectAry',$allowProjectAry);
            //是否為工安課
            $RootDept       = sys_param::getParam('ROOT_CHARGE_DEPT',1);
            $isRootDept     = ($RootDept == $be_dept_id)? true : false;
            Session::put('user.isRootDept',$isRootDept);
            //負責的廠區
            $dept_store     = b_factory_e::getStoreAry($be_dept_id);
            Session::put('user.dept_store',$dept_store);
            //負責的工程案件
            $projectAry     = e_project::getEmpProject($dept_store,0);
            Session::put('user.dept_project',$projectAry);
        }
        $store_name = b_factory::getName($store_id);
        Session::put('user.store_id',$store_id);
        Session::put('user.store_name',$store_name);
        Session::put('user.bcuste',$bcuste);
        Session::put('user.be_dept_id',$be_dept_id);
        Session::put('user.be_dept',$be_dept);
        Session::put('user.be_title',$be_title);
        Session::put('user.be_title_id',$be_title_id);
    }
}
