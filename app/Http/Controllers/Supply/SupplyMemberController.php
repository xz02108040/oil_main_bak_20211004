<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTitleTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_title;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\View\view_user;
use Storage;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class SupplyMemberController extends Controller
{
    use SupplyMemberTrait,BcustTrait,BcustATrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyMemberController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct(Request $request)
    {
        //身分驗證
        $this->middleware('auth');
        //讀取選限
        $this->uri              = SHCSLib::getUri($request->route()->uri);
        $this->isWirte          = 'N';
        //路由
        $this->hrefHome         = 'contractor';
        $this->hrefMain         = 'contractormember';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'person/';
        $this->hrefMainDetail2  = 'contractormemberlicense';
        $this->hrefMainDetail3  = 'etraningmember2';
        $this->hrefMainNew      = 'new_contractormember/';
        $this->routerPost       = 'postContractormember';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        $isRoot = ($this->isRootDept || $this->isRoot)? 1 : 0;
        //參數
        $no = 0;
        $out = $js ='';
        $closeAry       = SHCSLib::getCode('CLOSE');
        $isInProjectAry = SHCSLib::getCode('IS_JOIN_PROJECT',1);
        $passAry  = SHCSLib::getCode('PASS');
        $overAry  = SHCSLib::getCode('DATE_OVER');
        $pairAry  = SHCSLib::getCode('RFID_CARD_PAIRD');
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
            $supply= b_supply::getName($pid);
        }

        //是否參與工程案件
        $isInProject = ($request->isInProject)? $request->isInProject : '';
        if($request->has('clear'))
        {
            $isInProject = '';
            Session::forget($this->hrefMain.'.search');
        }
        if($isInProject)
        {
            Session::put($this->hrefMain.'.search.isInProject',$isInProject);
        } else {
            $isInProject = Session::get($this->hrefMain.'.search.isInProject','');
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyMemberList($pid,$isInProject);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->langText.'.supply_id',$pid);
        Session::put($this->langText.'.pid',$request->pid);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain.'?'.$param,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
        $html = $form->select('isInProject',$isInProjectAry,$isInProject,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        $memo = Lang::get($this->langText.'.supply_70');
        $form->addHtml(HtmlLib::Color($memo,'red',1));
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_21')]; //身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_22')]; //行動電話
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_23')]; //血型
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_24')]; //緊急聯絡人
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_53')]; //專業證照
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_27')]; //教育訓練
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_136')]; //尿檢
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_27')]; //工作身份
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_28')]; //工程案件資格
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_26')]; //教育訓練資格
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_29')]; //配卡資格
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_30')]; //違規
        $heads[] = ['title'=>Lang::get('sys_engineering.engineering_25')]; //通行資格

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->b_cust_id;
                $did          = SHCSLib::encode($value->b_cust_id);
                $name1        = $value->name; //
                $name2        = $value->nation_name .' / '.SHCSLib::genBCID($value->bc_id); //
                $name3        = $value->mobile1; //
                if($value->tel1)
                {
                    $name3 .= (strlen($name3))? '<br/>' : '';
                    $name3 .= $value->tel1;
                }
                $name4        = $value->blood.($value->bloodRH ? '('.$value->bloodRH.')' : ''); //
                $name5        = $value->kin_user; //
                $name5       .= $value->kin_kind_name ? '('.$value->kin_kind_name.')' : ''; //
                $name5       .= $value->kin_tel ? '<br/>'.$value->kin_tel : ''; //
                $name7        = $value->project; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseType  = $value->isClose == 'Y' ? 1 : 0;

                $name29        = $value->identitylist; //
                //尿檢
                $name30        = '<b>'.$value->ut_name.'</b>'; //
                //工程案件是否過期
                $name31        = isset($overAry[$value->isOver])? $overAry[$value->isOver] : ''; //
                $name31C       = $value->isOver == 'Y'? 2 : 5; //
                //教育訓練
                $name32        = isset($passAry[$value->isPass])? $passAry[$value->isPass] : ''; //
                $name32C       = $value->isPass == 'Y'? 2 : 5; //
                //是否配卡
                $name33        = isset($pairAry[$value->isPair])? $pairAry[$value->isPair] : ''; //
                $name33C       = $value->isPair == 'Y'? 2 : 5; //
                //違規
                $name34        = $value->isViolaction; //
                $name34C       = $value->isViolaction ? 5 : 1; //
                //白名單
                $isWhiteList   = $value->isViolaction ? 'N' : $value->isWhiteList;
                $name35        = isset($passAry[$isWhiteList])? $passAry[$isWhiteList] : ''; //
                $name35C       = $isWhiteList == 'Y'? 2 : 5; //

                //按鈕
                $IdentityBtn  = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','mid='.$did),Lang::get('sys_btn.btn_30'),3); //按鈕
                $TraningBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,'','mid='.$did),Lang::get('sys_btn.btn_30'),4); //按鈕

                $btn          = ($isCloseType || $this->isWirte != 'Y')? '' : HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '21'=>[ 'name'=> $IdentityBtn],
                            '22'=>[ 'name'=> $TraningBtn],
                            '7'=>[ 'name'=> $name7],
                            '30'=>[ 'name'=> $name30],
                            '29'=>[ 'name'=> $name29],
                            '31'=>[ 'name'=> $name31,'label'=> $name31C],
                            '32'=>[ 'name'=> $name32,'label'=> $name32C],
                            '33'=>[ 'name'=> $name33,'label'=> $name33C],
                            '34'=>[ 'name'=> $name34,'label'=> $name34C],
                            '35'=>[ 'name'=> $name35,'label'=> $name35C],
                            '99'=>[ 'name'=> $btn ]
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        unset($table);


        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //年紀參數
        $birth_min_param = sys_param::getParam('BIRTH_DATE_LIMIT1',18);
        $birth_max_param = sys_param::getParam('BIRTH_DATE_LIMIT2',65);

        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->bc_id || !$request->sex || !$request->blood || !$request->mobile1 )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(!$request->kind_user || !$request->kind_type || !$request->kind_tel)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1001'))
                ->withInput();
        }
        elseif(!$request->addr1)
        {
            //2020-11-10 地址改為必填
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1032'))
                ->withInput();
        }
        //身分證格式錯誤
        elseif(!CheckLib::isBcID($request->bc_id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10920'))
                ->withInput();
        }
        //身分證格式重複
        elseif(CheckLib::checkBCIDExist($request->bc_id,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10925'))
                ->withInput();
        }
        //Email格式錯誤
        elseif($request->email1 && !CheckLib::isMail($request->email1))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10921'))
                ->withInput();
        }
        //生日限制
        elseif($request->birth && $request->birth != '1970-01-01' && SHCSLib::birthday($request->birth) > $birth_max_param)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10923',['name'=>$birth_max_param]))
                ->withInput();
        }
        //生日限制
        elseif($request->birth && $request->birth != '1970-01-01' && SHCSLib::birthday($request->birth) < $birth_min_param)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10924',['name'=>$birth_min_param]))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $pid  = SHCSLib::decode($request->urlid);
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;

            if(!$pid && is_numeric($pid) && $pid > 0)
            {
                $msg = Lang::get($this->langText.'.supply_1000');
                return \Redirect::back()->withErrors($msg);
            }
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $filepath = $filename = $imagedata = $extension = '';

        //處理圖片
        if($request->hasFile('headImg'))
        {
            //人頭像比例
            $head_max_height = sys_param::getParam('USER_HEAD_HEIGHT',640);
            $head_max_width  = sys_param::getParam('SER_HEAD_WIDTH',360);
            $ImgFile    = $request->headImg;
            $extension  = $ImgFile->extension();
            $filesize   = $ImgFile->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','GIF'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10119'))
                    ->withInput();
            } elseif($filesize > $this->fileSizeLimit1) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $imagedata = file_get_contents($ImgFile);
                if(!$isNew)
                {
                    //圖片位置
                    $filepath = config('mycfg.user_head_path').date('Y/').$id.'/';
                    $filename = $id.'_head.'.$extension;
                    //轉換 圖片大小
                    if(!SHCSLib::tranImgSize($filepath.$filename,$imagedata,$head_max_width,$head_max_height))
                    {
                        $filepath = $filename = '';
                    }
                }
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['bc_type']           = 3;
        $upAry['bc_type_app']       = 0;
        $upAry['account']           = $request->bc_id;
        $upAry['password']          = substr($request->bc_id,-4);
        $upAry['isLogin']           = 'N';
        $upAry['isIN']              = 'N';

        $upAry['head_img_data']     = $imagedata;
        $upAry['head_img_ext']      = $extension;
        $upAry['sex']               = $request->sex;
        $upAry['bc_id']             = $request->bc_id;
        $upAry['birth']             = $request->birth;
        $upAry['blood']             = $request->blood;
        $upAry['bloodRh']           = $request->bloodRh;
        $upAry['tel1']              = $request->tel1;
        $upAry['mobile1']           = $request->mobile1;
        $upAry['email1']            = $request->email1;
        $upAry['addr1']             = $request->addr1;
        $upAry['kin_user']          = $request->kind_user;
        $upAry['kin_kind']          = $request->kind_type;
        $upAry['kin_tel']           = $request->kind_tel;

        $upAry['b_supply_id']       = $pid;

        //新增
        if($isNew)
        {
            $ret = $this->createBcust($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = 0;
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_a',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$request->urlid);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 單筆資料 新增
     */
    public function create($urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        $pid      = SHCSLib::decode($urlid);
        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?pid='.$urlid;
        }
        $sexAry         = SHCSLib::getCode('SEX',1);
        $bloodAry       = SHCSLib::getCode('BLOOD',1);
        $bloodrhAry     = SHCSLib::getCode('BLOODRH');
        $kindAry        = SHCSLib::getCode('PERSON_KIND',1);
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
        //view元件參數
        $hrefBack   = $this->hrefMain.$param;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //--- 帳號區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10901'),3);
        $form->addHtml( $html );

        //table
        $table = new TableLib();

        //姓名＋圖片
        $fhtml1 = $form->text('name','');
        $fhtml2 = $form->file('headImg');
        $fhtml2.= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="200" /></span>';
        $tBody[] = [
            '0'=>['name'=>Lang::get('sys_base.base_10707'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
            '3'=>['name'=> $fhtml2,'style'=>'width:35%;','row'=>3,'col'=>2],
        ];
        unset($fhtml1,$fhtml2);

        //承攬商
        $fhtml1 = b_supply::getName($pid);
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.supply_12'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        //身分
        $fhtml1 = $bctypeAry[3];
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get('sys_base.base_10718'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];
        $table->addBody($tBody);
        $form->addHtml( $table->output() );

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 個人資訊 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10902'),4);
        $form->addHtml( $html );
        //性別
        $html = $form->select('sex',$sexAry,'F');
        $form->add('nameT6', $html,Lang::get('sys_base.base_10905'),1);
        //身分證
        $html = $form->text('bc_id','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10906'),1);
        //生日
        $html = $form->date('birth','');
        $form->add('nameT3', $html,Lang::get('sys_base.base_10907'));
        //血型
        $html = $form->select('blood',$bloodAry,'');
        $form->add('nameT5', $html,Lang::get('sys_base.base_10908'),1);
        //血型ＲＨ
        $html = $form->select('bloodRh',$bloodrhAry,'');
        $form->add('nameT6', $html,Lang::get('sys_base.base_10909'));
        //電話
        $html = $form->text('tel1','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10910'));
        //行動電話
        $html = $form->text('mobile1','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10911'),1);
        //Email
        $html = $form->text('email1','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10912'));
        //地址
        $html = $form->text('addr1','',8);
        $form->add('nameT2', $html,Lang::get('sys_base.base_10913'),1);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 緊急聯絡人 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_10903'),5);
        $form->addHtml( $html );
        //緊急聯絡人
        $html = $form->text('kind_user','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10914'),1);
        //關係
        $html = $form->select('kind_type',$kindAry,'');
        $form->add('nameT5', $html,Lang::get('sys_base.base_10915'),1);
        //聯絡電話
        $html = $form->text('kind_tel','');
        $form->add('nameT2', $html,Lang::get('sys_base.base_10916'),1);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('bc_type',3);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('urlid',$urlid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("#birth").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            $("input[name=\'headImg\']").change(function() {
              readURL(this);
              $("#blah_div").hide();
            });
            function readURL(input) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $("#blah").attr("src", e.target.result);
                  $("#blah_div").show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
        });';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function getData($uid = 0)
    {
        $ret  = array();
        $data = Session::get($this->hrefMain.'.Record');
        //dd($data);
        if( $data && count($data))
        {
            if($uid)
            {
                foreach ($data as $v)
                {
                    if($v->id == $uid)
                    {
                        $ret = $v;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

}
