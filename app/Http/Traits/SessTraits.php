<?php
namespace App\Http\Traits;

use App\Lib\SHCSLib;
use Uuid;
use Session;
use Auth;
/**
 * Session
 * User: dorado
 */
trait SessTraits {

    /**
     * 取得 會員參數
     */
    protected function getBcustParam()
    {
        $this->b_cust_id        = Session::get('user.b_cust_id');
        $this->name             = Session::get('user.name');
        $this->user_title       = Session::get('user.user_title');
        $this->store_id         = Session::get('user.store_id',0);
        $this->store            = Session::get('user.store_name','');
        $this->b_menu_group_id  = Session::get('user.b_menu_group_id');
        $this->isRoot           = Session::get('user.isRoot','N');
        $this->login_suc_at     = Session::get('user.login_suc_at');
        $this->login_err_at     = Session::get('user.login_err_at');
        $this->sys_kind         = Session::get('user.sys_kind','X');
        $this->bc_type          = Session::get('user.bc_type','0');
        $this->sign_img         = Session::get('user.sign_img','');
        $this->sign_url         = Session::get('user.sign_url','');
        $this->be_dept_id       = Session::get('user.be_dept_id',0);
        $this->be_title_id      = Session::get('user.be_title_id',0);
        $this->allowDeptAry     = Session::get('user.allowDeptAry',[]);
        $this->allowProjectAry  = Session::get('user.allowProjectAry',[]);
        $this->isRootDept       = Session::get('user.isRootDept',false);
        $this->menu_auth        = Session::get('user.menu_auth',[]);
        $this->dept_store       = Session::get('user.dept_store',[]);
        $this->dept_project     = Session::get('user.dept_project',[]);
        $this->isSuperUser      = ($this->isRootDept || $this->isRoot == 'Y')? true : false;
    }

    /**
     * Menu
     */
    protected function getMenuParam()
    {
        $sys_menu = Session::get('user.sys_menu');
        $this->sys_menu = (is_object($sys_menu) || is_array($sys_menu))? SHCSLib::toArray($sys_menu) : array();
    }

    /**
     * 取得 系統代碼 參數
     */
    protected function getSysParam()
    {
        $sys_code = Session::get('user.sys_code');
        $this->CUST_SEX = $this->BLOOD = $this->BLOODRH = $this->BC_TYPE = $this->CUST_P_TYPE = [''=>''];

        if(count($sys_code))
        {
            //性別
            if(isset($sys_code->CUST_SEX) && count($sys_code->CUST_SEX))
            {
                $this->CUST_SEX  = $this->CUST_SEX + (array)$sys_code->CUST_SEX;
            }
            //血型
            if(isset($sys_code->BLOOD) && count($sys_code->BLOOD))
            {
                $this->BLOOD  = $this->BLOOD + (array)$sys_code->BLOOD;
            }
            //血型ＲＨ
            if(isset($sys_code->BLOODRH) && count($sys_code->BLOODRH))
            {
                $this->BLOODRH  = $this->BLOODRH + (array)$sys_code->BLOODRH;
            }
            //會員類型
            if(isset($sys_code->BC_TYPE) && count($sys_code->BC_TYPE))
            {
                $this->BC_TYPE  = $this->BC_TYPE + (array)$sys_code->BC_TYPE;
            }
        }
    }

    /**
     * 取得 下拉選單內容
     * @param string $id
     * @return array
     */
    protected function getSessSelect($id = 'X')
    {
        $ret  = array(''=>'');
        $data = (array)Session::get('select.'.$id);
        return $ret + $data;
    }
}
