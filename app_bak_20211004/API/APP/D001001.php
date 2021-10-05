<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\WorkPermit\WorkCheckKindTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitIdentityTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorklineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Lib\SHCSLib;
use App\Model\App\app_menu;
use App\Model\App\app_menu_auth;
use App\Model\bc_type_app;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Engineering\et_course;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_project_factory;
use App\Model\View\view_supply_user;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_topic_type;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D001001 登入機制.
 * 目的：ＴＯＫＥＮ
 *
 */
class D001001 extends JsonApi
{
    use BcustTrait,SupplyTrait,WorkPermitTrait,WorkPermitKindTrait,WorkPermitDangerTrait,WorkPermitWorkItemTrait;
    use WorkPermitIdentityTrait,SupplyMemberTrait,PushTraits;
    use WorkPermitProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicTrait,WorkPermitTopicOptionTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait,WorkPermitCheckTopicOptionTrait;
    use EngineeringDeptTrait,EngineeringFactoryTrait,FactoryDeviceTrait,EngineeringCarTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait,WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderlineTrait;
    use WorkPermitWorkItemCheckTrait,WorkPermitWorklineTrait,WorkCheckKindTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        //參數
        $isSuc    = 'Y';
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        $isChkErrLoginTimesRule = sys_param::getParam('API_ERR_LOGIN_MAX_TIMES',3);
        $this->tokenType    = 'app';     //ＡＰＩ模式：ＡＰＰ
        $this->errCode      = 'E00004';//格式不完整
        $this->b_supply_id  = 0;
        $this->be_dept_id   = 0;
        $now_version_min    = sys_param::getParam('APP_VERSION_MIN',0);

        //格式檢查
        if(isset($jsonObj->login))
        {
            //1.1 參數資訊
            $account   = (isset($jsonObj->login->acct))?        trim($jsonObj->login->acct) : ''; //帳號
            $password  = (isset($jsonObj->login->pwd))?         $jsonObj->login->pwd : '';  //密碼
            $imei      = (isset($jsonObj->login->imei))?        $jsonObj->login->imei : '';  //IMEI「手機識別碼」
            $pusher_id = (isset($jsonObj->login->pusher_id))?   trim($jsonObj->login->pusher_id) : '';  //推播
            $isIOS     = (isset($jsonObj->login->ios))?         $jsonObj->login->ios : '';  //推播
            $version   = (isset($jsonObj->login->version))?     $jsonObj->login->version : 0;  //推播

            $GPSX      = (isset($jsonObj->login->GPSX) && $jsonObj->login->GPSX)? $jsonObj->login->GPSX : '';
            $GPSY      = (isset($jsonObj->login->GPSY) && $jsonObj->login->GPSY)? $jsonObj->login->GPSY : '';


            //2.1 帳號/密碼不可為空
            if(!$account || !$password)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00100101';//帳號/密碼不可為空
            }
            //2.1.2 登入失敗，ＡＰＰ版本過低，請更新到最新版
//            if(!$version || !is_numeric($version) || $version < $now_version_min)
//            {
//                $isSuc          = 'N';
//                $this->errCode  = 'E00100105';//登入失敗，ＡＰＰ版本過低，請更新到最新版
//                $this->errParam1= $version;//登入失敗，ＡＰＰ版本過低，請更新到最新版
//                $this->errParam2= $now_version_min;//登入失敗，ＡＰＰ版本過低，請更新到最新版
//            }

            //2.2 [系統參數]是否嘗試登入很多次（登入次數三次檢查）
            if($isSuc == 'Y' && $isChkErrLoginTimesRule && !CheckLib::isOverLoginErrLimit($account,$imei,$clientIp))
            {
                $maxtime        = sys_param::getParam('API_ERR_LOGIN_TIME_RANGER',300);
                $isSuc          = 'N';
                $this->errCode  = 'E00100102';//登入錯誤超過
                $this->errParam1= floor($maxtime/ 60);//幾分鐘內錯誤登入次數
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                if(CheckLib::isAppAccount($account, $password, 1, 2,['ip'=>$clientIp,'imie'=>$imei]))
                {
                    //3.1 使用者ＩＤ
                    $this->user_id      = Auth::id();
                    $this->bc_type      = Auth::user()->bc_type;    //帳號身份
                    $this->bc_type_app  = Auth::user()->bc_type_app;//ＡＰＰ登入身份
                    $this->b_cust       = view_user::find($this->user_id);
                    $this->imei         = $this->b_cust->imei; // 首次取得 imei 若為空則顯示隱私權 (進行登錄後則會更新裝置的 imei 至 b_cust，在此必須在更新前取出 imei 判斷是否為首次登錄)

                    if($this->bc_type == 3)
                    {
                        $this->b_supply_id  = view_supply_user::getSupplyId($this->user_id);
                        $isOk = ($this->b_supply_id)? 1 : 0;
                    } else {
                        $this->be_dept_id   = view_dept_member::getDept($this->user_id);
                        $isOk = ($this->be_dept_id)? 1 : 0;
                    }

                    if($isOk)
                    {
                        //3.2 更新使用者登入資訊
                        $upAry = [];
                        if(!is_null($imei)) $upAry['imei']  = SHCSLib::tranStr2($imei);
                        if($pusher_id)  $upAry['pusher_id'] = $pusher_id;
                        if($GPSX)       $upAry['GPSX']      = $GPSX;
                        if($GPSY)       $upAry['GPSY']      = $GPSY;
                        if($isIOS)      $upAry['isIOS']     = 'Y';

                        //3.3 本次登入SISSON
                        $session_id = Session::getId();
                        //3.3.1 如果SESSION 與上次不同
                        if(Auth::user()->last_session != $session_id)
                        {
                            //更換session
                            $upAry['last_session'] = $session_id;
                            //Token失效
                            TokenLib::closeToken($this->user_id,$this->tokenType);
                        }
                        //3.3.9 如果有要更新[會員帳號資料]
                        if(count($upAry))
                        {
                            //3.3.9-1 註銷該 推播ＩＤ
                            if($pusher_id)
                            {
                                $this->closeBcustPuserID($pusher_id,$this->user_id);
                            }
                            //3.3.9-2 更新個人帳號資料
                            $this->setBcust($this->user_id,$upAry,$this->user_id);
                        }

                        //3.9 Token
                        $getTokenRet = TokenLib::getToken($this->tokenType,$this->user_id);
                        //如果正確取得Token
                        if($getTokenRet['ret'] == 'Y') {
                            $this->reply    = 'Y';
                            $this->errCode  = '';
                            $this->token    = $getTokenRet['token'];    //帳號TOKEN
                            $this->apiKey   = $getTokenRet['apiKey'];   //圖片ＡＰＩＫＥＹ
                        }
                    } else {
                        $this->errCode  = ($this->bc_type == 3)? 'E00200104' : 'E00200103';//該帳號非承攬商/該帳號非職員
                    }

                } else {
                    // E00100103 => 登入失敗，請確認帳號與ＡＰＰ身份是否開通, E00100104 => 登入失敗，請確認帳號與密碼是否正確
                    $this->errCode  = User::isLogin($account,1) ? 'E00100104' : 'E00100103'; //登入錯誤
                }
            }
        }

        //2. 產生ＪＳＯＮ Ａrray
        $ret = $this->genReplyAry();
        //3. 回傳json 格式
        return $ret;
    }

    /**
     * 產生回傳內容陣列
     * @param $token
     * @return array
     */
    public function genReplyAry()
    {
        //回傳格式
        $ret = $this->getRet();

        if($this->reply == 'Y')
        {
            //2019-08-31
            //推播測試 登入成功
            //$this->pushToLoginSuccess($this->user_id);

            $app_menu_group_id  = isset($this->b_cust->app_menu_group_id)? $this->b_cust->app_menu_group_id : 0;
            $be_dept_id = $be_title_id = $b_supply_id = 0;

            if($this->bc_type == 2)
            {
                $be_title_id        = view_dept_member::getTitle($this->user_id);
                $RootDept           = sys_param::getParam('ROOT_CHARGE_DEPT',1);
                $isRootDept         = ($RootDept == $this->be_dept_id)? true : false;
                $be_dept_id         = $isRootDept? 0 : $this->be_dept_id;
            }


            //TOKEN
            $ret['token']           = $this->token;  //登入授權
            $ret['apiKey']          = $this->apiKey; //圖片授權
            $ret['imei']            = $this->imei; //imei
            $ret['permit_version']  = 1; //TODO 工作許可證版本號碼
            $ret['privacy']         = Lang::get('sys_base.privacy');
            //使用者基本資料
            $user = $this->getApiUser($this->user_id,$this->apiKey);
            $ret['user']            = $user;
            $ret['be_title_id']     = $be_title_id;
            //app menu
            $ret['menu']            = app_menu_auth::getAppAuthMenuData($app_menu_group_id);

            //搜尋參數
//            $ret['search']          = [];
//            //
//            $ret['search']['door_mode'] = [['id'=>'M','name'=>'承攬商成員'],['id'=>'C','name'=>'承攬商車輛']];
            //搜尋參數:全門市
//            $ret['search']['store']             = ($this->b_supply_id)? view_project_factory::getSupplyLocal($this->b_supply_id,0) : b_factory::getApiSelect(0);
//            $ret['search']['be_dept']           = be_dept::getApiSelect(0,0);
//            $ret['search']['b_factory_a']       = b_factory_a::getApiSelect(0,0);
//            $ret['search']['b_factory_b']       = b_factory_b::getApiSelect(0,0);
//            $ret['search']['b_factory_d']       = b_factory_d::getApiSelect(0,0);
//            $ret['search']['b_factory_e']       = b_factory_e::getApiSelect(0,0);
            //搜尋參數:工程案件
//            $ret['search']['project']           = e_project::getApiSelect($this->b_supply_id);//工程案件
//            $ret['search']['project2']          = e_project::getApiSelect($this->b_supply_id,$be_dept_id);//工程案件
//            $ret['search']['project_type']      = e_project_type::getApiSelect();//工程案件分類
//            $ret['search']['project_aproc']     = SHCSLib::getCode('ENGINEERING_APROC',1,1); //進度

            //搜尋參數:承攬商
//            $ret['search']['supply']            = b_supply::getApiSelect($this->b_supply_id);//工程案件分類
            //搜尋參數:工安違規
//            $ret['search']['violation']         = e_violation::getApiSelect();
//            $ret['search']['violation_law']     = e_violation_law::getApiSelect();
//            $ret['search']['violation_type']    = e_violation_type::getApiSelect();
//            $ret['search']['violation_punish']  = e_violation_punish::getApiSelect();
            //搜尋參數:教育訓練
//            $ret['search']['course']            = et_course::getApiSelect();
            //工作許可證
//            $ret['search']['shift']             = wp_permit_shift::getSelect(0,0,1); //班別
//            $ret['search']['wp_permit_danger']  = SHCSLib::getCode('PERMIT_DANGER',1,1);
//            $ret['search']['permit_aproc']      = SHCSLib::getCode('PERMIT_APROC',1,1);
//            $ret['search']['extended_aproc']    = SHCSLib::getCode('EXTENDED_APROC',1,1);
//            $ret['search']['wp_item']           = $this->getApiWorkPermitKind(1);
//            $ret['search']['wp_danger']         = $this->getApiWorkPermitDanger(0,1,'N');
//            $ret['search']['wp_check']          = $this->getApiWorkCheckKind();
//            $ret['search']['wp_line']           = $this->getApiWorkPermitWorkLine();
            //工作許可證離線題目&版本號碼
//            $ret['permit']['wp_work']           = $this->getApiWorkPermitAllProcessTopic(1);
//            $ret['permit']['wp_work_patrol']    = $this->getApiWorkPermitProcessTopic(1,7,0,'',[9],'Y');;
            $ret['permit']['ver']               = wp_permit::getAt(1);

//            //供應商
//            if($user->bc_type == 3)
//            {
//                $b_supply_id   = b_supply_member::getSupplyId($this->user_id);
//                $ret['supply']      = $this->getApiSupplyData($b_supply_id);
//                $ret['supply_id']   = $b_supply_id;
//                $store       = view_project_factory::getSupplyLocal($b_supply_id,1); //廠區->場地
//                $project     = view_door_supply_member::getProject($b_supply_id);//工程案件
//            }
//            //職員
//            else
//            {
//                $ret['dept']        = view_dept_member::getData($this->user_id);
//                $store              = b_factory::getApiSelect(); //廠區->場地
//                //$store_id = isset($ret['dept']['b_factory_id'])? $ret['dept']['b_factory_id'] : 0;
//                $dept_id  = isset($ret['dept']['be_dept_id'])?   $ret['dept']['be_dept_id'] : 0;
//                $deptStoreAry = b_factory_e::getStoreAry($dept_id);
//
//                $project     = e_project::getEmpProject($deptStoreAry,0,1,1);//工程案件
//            }
//            if(!count($project)) $project[] = Lang::get('sys_base.base_10021');
//            if(!count($store))   $store[]   = Lang::get('sys_base.base_10021');
//            $ret['project'] = $project;
//            $ret['store']   = $store;
//            $ret['supply_select'] = b_supply::getSelect2();


            //基本資料
            //$ret['bc_type_app']     = bc_type_app::getSelect(0,0); //進度
            //$ret['mode']            = SHCSLib::getCode('MEN_CAR_MODE',1,1); //儀表板-人車模式



            //工作許可證
//            $ret['wp_permit_danger']= SHCSLib::getCode('PERMIT_DANGER',1,1); //進度
//            $ret['permit_aproc']    = SHCSLib::getCode('PERMIT_APROC',1,1); //進度
//            $ret['list_aproc']      = SHCSLib::getCode('PERMIT_LIST_APROC',1,1,['C','F']); //進度 'R','O',
//            $ret['wp_permit_kind']  = $this->getApiWorkPermitKind(1); //工作許可證種類->危險等級->工作項目
//            $ret['wp_permit']       = $this->getApiWorkPermit(); //工作許可證種類->設計模型
//            $ret['wp_topic_type']   = wp_topic_type::getApiSelect(); //檢核項目類型
//            $ret['wp_option_type']  = SHCSLib::getCode('WP_OPTION_TYPE',1,1); //工作許可證＿檢核選項類別
            //$ret['danger_check']    = $this->getApiWorkPermitDanger();

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
