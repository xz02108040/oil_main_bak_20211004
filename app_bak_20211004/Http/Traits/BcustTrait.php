<?php

namespace App\Http\Traits;

use App\Model\b_cust_a;
use App\Model\b_menu_group;
use App\Model\bc_type_app;
use App\Model\c_menu_group;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply_member;
use App\Model\v_cust;
use App\Model\v_stand;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_supply_user;
use App\Model\View\view_user;
use Hash;
use DB;

/**
 * 使用者 函式庫.
 * User: dorado
 *
 */
trait BcustTrait
{
    /**
     * 新增 使用者帳號
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBcust($data,$mod_user = 1)
    {
        $ret = false;
        $b_cust_id = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new User();
        $INS->name              = $data->name;
        $INS->bc_type           = $data->bc_type;
        $INS->nation            = isset($data->nation)? $data->nation : 1;
        $def_b_menu_group_id    = $INS->bc_type == 2? 1 : 1;
        $def_c_menu_group_id    = $INS->bc_type == 3? 2 : 1;

        $INS->account           = $data->account;
        $INS->password          = bcrypt($data->password);
        $INS->password1         = sha1($data->password);
        $INS->b_menu_group_id   = isset($data->b_menu_group_id)? $data->b_menu_group_id : $def_b_menu_group_id;
        $INS->c_menu_group_id   = isset($data->c_menu_group_id)? $data->c_menu_group_id : $def_c_menu_group_id;
        $INS->app_menu_group_id = ($data->bc_type == 3) ? 2 : 1 ;
        $INS->isLogin           = (isset($data->isLogin) && $data->isLogin == 'Y')? 'Y' : 'N';
        $INS->isIN              = (isset($data->isIN) && $data->isIN == 'Y')? 'Y' : 'N';
        $INS->b_supply_rp_member_id = (isset($data->b_supply_rp_member_id))? $data->b_supply_rp_member_id : 0;
        $INS->new_user          = $mod_user;
        $INS->mod_user          = $mod_user;

        //如果新增成功
        if($INS->save())
        {
            $b_cust_id = $INS->id;
            $data->b_cust_id = $b_cust_id;
            //新增 個人資訊
            $this->createBcustA($data,$this->b_cust_id);
            //如果有 部門/職稱，則新增 職員
            if($b_cust_id && $INS->bc_type == 2 && isset($data->be_dept_id) &&$data->be_dept_id )
            {
                $this->createEmp($data,$mod_user);
            }
            //如果有 承攬商，則新增 承攬商成員
            if($b_cust_id && $INS->bc_type == 3 && isset($data->b_supply_id) && $data->b_supply_id)
            {
                $this->createSupplyMember($data,$mod_user);
            }
            //如果是自動建立帳號，則用會員ＩＤ當作 帳密
            if(isset($data->isAutoAccount) && $data->isAutoAccount == 'Y')
            {
                $INS->account           = $b_cust_id;
                $INS->password          = bcrypt($b_cust_id);
                $INS->password1         = sha1($b_cust_id);
                $INS->save();
            }
        }


        return $b_cust_id;
    }

    /**
     * 修改 使用者帳號
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBcust($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id ) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = User::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        if(isset($data->nation) && $data->nation && $data->nation !== $UPD->nation)
        {
            $isUp++;
            $UPD->nation = $data->nation;
        }
        if(isset($data->account) && $data->account && $data->account !== $UPD->account)
        {
            $isUp++;
            $UPD->account = $data->account;
        }
        if(isset($data->password) && $data->password && !Hash::check($UPD->password, $data->password))
        {
            $isUp++;
            $UPD->password  = bcrypt($data->password);
            $UPD->password1 = sha1($data->password);
        }
        if(isset($data->b_menu_group_id) && $data->b_menu_group_id && $data->b_menu_group_id !== $UPD->b_menu_group_id)
        {
            $isUp++;
            $UPD->b_menu_group_id = $data->b_menu_group_id;
            $UPD->app_menu_group_id = 1;
        }
        if(isset($data->c_menu_group_id) && $data->c_menu_group_id && $data->c_menu_group_id !== $UPD->c_menu_group_id)
        {
            $isUp++;
            $UPD->c_menu_group_id   = $data->c_menu_group_id;
            $UPD->app_menu_group_id = 2;
        }
        if(isset($data->bc_type) && $data->bc_type && $data->bc_type !== $UPD->bc_type)
        {
            $isUp++;
            $UPD->bc_type = $data->bc_type;
        }
        if(isset($data->bc_type_app) && $data->bc_type_app && $data->bc_type_app !== $UPD->bc_type_app)
        {
            $isUp++;
            $UPD->bc_type_app = $data->bc_type_app;
        }
        if(isset($data->isLogin) && $data->isLogin && $data->isLogin !== $UPD->isLogin)
        {
            $isUp++;
            $UPD->isLogin = $data->isLogin;
        }
        if(isset($data->isIOS) && $data->isIOS && $data->isIOS !== $UPD->isIOS)
        {
            $isUp++;
            $UPD->isIOS = $data->isIOS;
        }
        if(isset($data->last_session) && $data->last_session && $data->last_session !== $UPD->last_session)
        {
            $isUp++;
            $UPD->last_session = $data->last_session;
        }
        if(isset($data->imei) && $data->imei && $data->imei !== $UPD->imei)
        {
            $isUp++;
            $UPD->imei = $data->imei;
        }
        if(isset($data->pusher_id) && strlen($data->pusher_id) && $data->pusher_id !== $UPD->pusher_id)
        {
            $isUp++;
            $UPD->pusher_id = $data->pusher_id;
        }
        if(isset($data->GPSX) && $data->GPSX !== $UPD->GPSX)
        {
            $isUp++;
            $UPD->GPSX = $data->GPSX;
        }
        if(isset($data->GPSY) && $data->GPSY !== $UPD->GPSY)
        {
            $isUp++;
            $UPD->GPSY = $data->GPSY;
        }
        if(isset($data->sign_img) && $data->sign_img && $data->sign_img !== $UPD->sign_img)
        {
            $isUp++;
            $UPD->sign_img = $data->sign_img;
        }
        //是否可進入
        if(isset($data->isIN) && $data->isIN && $data->isIN !== $UPD->isIN)
        {
            $isUp++;
            if($data->isIN == 'Y')
            {
                $UPD->isIN = $data->isIN;
                $UPD->chgIN_user = $mod_user;
                $UPD->chgIN_stamp = $now;
            } else {
                $UPD->isIN = 'N';
            }
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose = $data->isClose;
                $UPD->close_user = $mod_user;
                $UPD->close_stamp = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 清空指定的推播ＴＯＫＥＮ
     * @param $pusher_id
     * @param int $user_id
     * @return bool
     */
    public function closeBcustPuserID($pusher_id, $user_id = 0)
    {
        if(!$pusher_id) return false;
        $UPD = [];
        $UPD['pusher_id'] = '';

        $data = DB::table('b_cust')->where('pusher_id',$pusher_id);
        if($user_id)
        {
            $data = $data->where('id','!=',$user_id);
        }
        return $data->update($UPD);

    }
    /**
     * 清空指定的推播ＴＯＫＥＮ
     * @param $pusher_id
     * @param int $user_id
     * @return bool
     */
    public function closeMyPuserID($user_id)
    {
        if(!$user_id) return false;
        $UPD = [];
        $UPD['pusher_id'] = '';

        $data = DB::table('b_cust')->where('id',$user_id);
        return $data->update($UPD);

    }

    /**
     * 取得 User 選單
     *
     * @return array
     */
    public function getApiCustList($bctype = [], $unitName = '')
    {
        $ret = array();
        $beGroupAry = b_menu_group::getSelect();
        $ceGroupAry = c_menu_group::getSelect();
        $bctypeAry  = SHCSLib::getCode('BC_TYPE');
        //取第一層
        $data = User::orderby('id');
        if(count($bctype))
        {
            $data = $data->whereIn('bc_type',$bctype);
            if (!empty($unitName)) {
                foreach ($bctype as $bctype_v) {
                    if ($bctype_v == 2) {
                        $data->whereRaw("id IN (SELECT b_cust_id FROM b_cust_e JOIN be_dept ON be_dept.id = b_cust_e.be_dept_id WHERE be_dept.name LIKE '%$unitName%')");
                    } else if ($bctype_v == 3) {
                        $data->whereRaw("id IN (SELECT b_cust_id FROM b_supply_member JOIN b_supply ON b_supply.id = b_supply_member.b_supply_id WHERE b_supply.name LIKE '%$unitName%')");
                    }
                }
            }
        }
        $data = $data->orderby('bc_type')->orderby('isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['b_menu_group']       = (isset($beGroupAry[$v->b_menu_group_id]))? $beGroupAry[$v->b_menu_group_id] : '';
                $data[$k]['c_menu_group']       = (isset($ceGroupAry[$v->c_menu_group_id]))? $ceGroupAry[$v->c_menu_group_id] : '';
                $data[$k]['bc_type_name']       = isset($bctypeAry[$v->bc_type])? $bctypeAry[$v->bc_type] : '';
                $data[$k]['bc_type_unit_name']  = '';
                if ($v->bc_type == 2) {
                    $res = b_cust_e::join('be_dept', 'be_dept.id', 'b_cust_e.be_dept_id')->where('b_cust_e.b_cust_id', $v->id)->select('be_dept.name')->first();
                    $data[$k]['bc_type_unit_name']  = isset($res) ? $res->name : '';
                } else if ($v->bc_type == 3) {
                    $res = b_supply_member::join('b_supply', 'b_supply.id', 'b_supply_member.b_supply_id')->where('b_supply_member.b_cust_id', $v->id)->select('b_supply.name')->first();
                    $data[$k]['bc_type_unit_name']  = isset($res) ? $res->name : '';
                }
                $data[$k]['bc_type_app_name']   = bc_type_app::getName($v->bc_type_app);
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 User 選單
     *
     * @return array
     */
    public function getApiMemberList($supply_id = 0)
    {
        $ret = array();
        $beGroupAry = b_menu_group::getSelect();
        $ceGroupAry = c_menu_group::getSelect();
        $bctypeAry  = SHCSLib::getCode('BC_TYPE');
        //取第一層
        $data = User::join('b_supply_member as s','s.b_cust_id','=','b_cust.id')->select('b_cust.*','s.b_supply_id');
        $data = $data->where('s.b_supply_id',$supply_id);
        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['b_menu_group']       = (isset($beGroupAry[$v->b_menu_group_id]))? $beGroupAry[$v->b_menu_group_id] : '';
                $data[$k]['c_menu_group']       = (isset($ceGroupAry[$v->c_menu_group_id]))? $ceGroupAry[$v->c_menu_group_id] : '';
                $data[$k]['bc_type_name']       = isset($bctypeAry[$v->bc_type])? $bctypeAry[$v->bc_type] : '';
                $data[$k]['bc_type_app_name']   = bc_type_app::getName($v->bc_type_app);
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 User 自己的資料
     *
     * @return array
     */
    public function getApiUser($id , $apikey = '')
    {
        $ret = array();
        $apiParam   = ($apikey)? ('?key='.$apikey) : '';
        $bctypeAry  = SHCSLib::getCode('BC_TYPE');
        //取第一層
        $data = view_user::find($id);

        if(isset($data->b_cust_id))
        {
            $ret['b_cust_id']           = $data->b_cust_id;
            $ret['name']                = $data->name;
            $ret['bc_type']             = $data->bc_type;
            $ret['bc_type_name']        = (isset($bctypeAry[$data->bc_type]))? $bctypeAry[$data->bc_type] : '';
            $ret['head_img']            = url('img/User/'.SHCSLib::encode($id).$apiParam);

            if($data->bc_type == 3)
            {
                $ret['unit']   = view_supply_user::getSupplyName($data->b_cust_id);
            } else {
                $ret['unit']   = view_dept_member::getDept($data->b_cust_id,2);
            }
        }

        return $ret;
    }

    /**
     *
     */
    public function chgBcustHasNotPwd()
    {
        $isUp = 0;
        $data = User::where('password','')->where('isClose','N');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $upAry = [];
                $upAry['password'] = ($val->bc_type == 3)? substr($val->account,-4):$val->account;
                $this->setBcust($val->id,$upAry,'1000000000');
                $isUp++;
            }
        }
        return $isUp;
    }
}
