<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_engineering_identity_a;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_rp_member_ei;
use App\Model\User;
use Illuminate\Http\Request;
use Storage;
use Session;
use Lang;
use Auth;

class SupplyMemberIdentityController extends Controller
{
    use SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyMemberIdentityController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員_擁有工程身分
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
        $this->hrefHome         = 'contractormember';
        $this->hrefMain         = 'contractormemberidentity';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'contractormemberidentity/';
        $this->hrefMainDetail2  = 'contractormemberlicense/';
        $this->hrefMainNew      = 'new_contractormemberidentity/';
        $this->hrefMainNew2     = 'new_contractormemberlicense/';
        $this->routerPost       = 'postContractormemberidentity';
        $this->routerPost2      = 'contractormemberidentityCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title3');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list3');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new3');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit3');//編輯

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
        //參數
        $no = 0;
        $out = $js ='';
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $closeAry  = SHCSLib::getCode('CLOSE');
        $overAry   = SHCSLib::getCode('DATE_OVER');
        $mid       = SHCSLib::decode($request->mid);
        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param1 = 'mid='.$request->mid;
            $param2 = '?pid='.Session::get($this->langText.'.pid',SHCSLib::encode($supply_id));
            $supply = b_supply::getName($supply_id);
            $member = User::getName($mid);
            $isCloseType = User::isClose($mid);
        }

        $cid      = $request->cid;
        if($request->has('clear'))
        {
            $cid = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','Y');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        $close = ($cid == 'N')? 'Y' : 'N';
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply.$Icon.$member;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->mid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome.$param2;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyMemberIdentityList($supply_id,$mid,$close);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain.'?'.$param1,'POST','form-inline');
        if(!$isCloseType)
        {
            $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        }
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回

        $form->addHr();
        $html = $form->select('cid',$overAry,$cid,2,Lang::get($this->langText.'.supply_7'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //工程身分
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_33')]; //有效期限
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_7')]; //有效期限

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $member; ///
                $name2        = $value->type; ///
                $name3        = $value->edate; ///
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色
                $btnStr       = $value->isClose == 'Y' ? 'btn_30' : 'btn_13';
                //按鈕
                $btn          = ($isCloseType)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param1),Lang::get('sys_btn.'.$btnStr),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $tBody     = [];
        $closeAry  = SHCSLib::getCode('CLOSE');
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $mid       = SHCSLib::decode($request->mid);
        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param  = '?mid='.$request->mid;
            $param1 = 'mid='.$request->mid.'&isIdentity=1'.'&back='.$urlid;
            $supply = b_supply::getName($supply_id);
            $member = User::getName($mid);
        }
        $id = SHCSLib::decode($urlid);
        $typeAry = b_supply_engineering_identity::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain.$param;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        $hrefNew        = $this->hrefMainNew2.$request->mid.'?isIdentity=1';
        $btnNew         = $this->pageNewBtn;
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->type; //
            $A2         = $getData->edate; //
            //證照證明
            $getFileAry = $this->getApiSupplyMemberLicenseList($supply_id,$mid,$id);

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $isClose    = isset($closeAry[$getData->isClose])? $closeAry[$getData->isClose] : '' ; //停用

            $isOver     = (strtotime($A2) >= strtotime(date('Y-m-d')))? 0 : 1;
            $isOver     = ($isOver || $A99)? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = $supply;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //成員姓名
        $html = $member;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'));
        //工程身分
        $html = $A1;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_51'),1);
        //有效日期
        $html = $A2;

        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_33'));
        //停用
        $html = ($A99)? HtmlLib::Color($isClose,'red',1) : $form->checkbox('isClose','Y',$A99);
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        $form->add('isCloseT',$html,Lang::get($this->langText.'.supply_18'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //---證照證明 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_31'),3);
        $form->addHtml( $html );
        if(!($isOver))
        {
            $html = $form->linkbtn($hrefNew, $btnNew,2);
            $form->addHtml( $html );
        }

        //table
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_32')];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_71')];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_33')];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_34'),'style'=>'width:15%;']; //
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_35'),'style'=>'width:15%;']; //
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_36'),'style'=>'width:15%;']; //

        $table->addHead($heads,1);

        if(count($getFileAry))
        {
            foreach ($getFileAry as $val)
            {
                $fileLink1 = ($val->filePath1)? $form->linkbtn($val->filePath1, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                $fileLink2 = ($val->filePath2)? $form->linkbtn($val->filePath2, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                $fileLink3 = ($val->filePath3)? $form->linkbtn($val->filePath3, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                //按鈕
                $btn       = ($A99)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,$val->id,$param1),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = [
                    '0' =>['name'=> $val->license,'b'=>1],
                    '11'=>['name'=> $val->license_code],
                    '1' =>['name'=> $val->edate],
                    '2' =>['name'=> $fileLink1],
                    '3' =>['name'=> $fileLink2],
                    '4' =>['name'=> $fileLink3],
                    '99'=>['name'=> $btn],
                ];
            }
        }

        $table->addBody($tBody);
        $form->addHtml( $table->output() );

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('urlid',$request->mid);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                startDate: "today",
                language: "zh-TW"
            });
        });';

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
        //資料不齊全
        if( !$request->id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        $this->getBcustParam();
        $mid        = SHCSLib::decode($request->urlid);
        $supply_id  = Session::get($this->langText.'.supply_id',0);
        $id         = SHCSLib::decode($request->id);
        $ip         = $request->ip();
        $menu       = $this->pageTitleMain;
        $isNew      = ($id > 0)? 0 : 1;
        $action     = ($isNew)? 1 : 2;
        $licenseAry = [];

        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        }

        if($request->isClose != 'Y')
        {
            if(!isset($request->license_id) || !count($request->license_id))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1003'))
                    ->withInput();
            }
            //該工程身份已經存在
            elseif($isNew && b_supply_member_ei::isExist($mid,$request->type_id))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1005'))
                    ->withInput();
            }

            $licenseAry = [];
            if(count($request->license_id))
            {
                foreach ($request->license_id as $iid => $val)
                {
                    $edate = isset($val['edate']) ? $val['edate'] : '';
                    $license_code = isset($val['license_code']) ? $val['license_code'] : '';
                    $edate_type   = isset($val['edate_type']) ? $val['edate_type'] : 'edate';
                    $file1        = isset($val['file1']) ? $val['file1'] : '';
                    $file2        = isset($val['file2']) ? $val['file2'] : '';
                    $file3        = isset($val['file3']) ? $val['file3'] : '';
                    //dd([$iid,$val]);

                    if($isNew)
                    {
                        if ((!$file1 && !$file2 && !$file3))
                        {
                            //日期 ＆ 檔案 沒有上傳
                            return \Redirect::back()
                                ->withErrors(Lang::get($this->langText . '.supply_1018'))
                                ->withInput();

                        }
                        elseif($edate_type == 'edate' && strtotime($edate) < strtotime(date('Y-m-d')))
                        {
                            //日期不可小於今日
                            return \Redirect::back()
                                ->withErrors(Lang::get($this->langText.'.supply_1006'))
                                ->withInput();
                        }
                    }

                    if(!$iid)
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText.'.supply_1003'))
                            ->withInput();
                    }
                    elseif(!$edate)
                    {
                        //日期 ＆ 檔案 沒有上傳
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText . '.supply_1018'))
                            ->withInput();
                    }
                    elseif(!CheckLib::isDate($val['edate']))
                    {
                        //日期格式不正確
                        return \Redirect::back()
                            ->withErrors(Lang::get($this->langText.'.supply_1004'))
                            ->withInput();
                    }
                    else{
                        $fileN = $fileData = '';
                        //檔案
                        if($file1)
                        {
                            $File       = $file1;
                            $extension  = $File->extension();
                            $filesize   = $File->getSize();
                            //dd([$File,$extension]);
                            //[錯誤]格式錯誤
                            if(!in_array(strtoupper($extension),['JPG','PNG','GIF','PDF','JPEG'])){
                                return \Redirect::back()
                                    ->withErrors(Lang::get('sys_base.base_10119'))
                                    ->withInput();
                            }elseif($filesize > $this->fileSizeLimit1){
                                return \Redirect::back()
                                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                                    ->withInput();
                            } else {
                                $fileN      = $extension;
                                $fileData   = file_get_contents($File);
                            }
                        }
                        $licenseAry[$iid] = [];
                        $licenseAry[$iid]['edate']          = $edate;
                        $licenseAry[$iid]['license_code']   = $license_code;
                        $licenseAry[$iid]['file1N']         = $fileN;
                        $licenseAry[$iid]['file1']          = $fileData;
                        $licenseAry[$iid]['file2N']         = '';
                        $licenseAry[$iid]['file2']          = '';
                        $licenseAry[$iid]['file3N']         = '';
                        $licenseAry[$iid]['file3']          = '';
                    }
                }
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }

        if($request->isClose != 'Y')
        {
            $upAry['license']           = $licenseAry;
            $upAry['type_id']           = $request->type_id;
            $upAry['b_supply_id']       = $supply_id;
            $upAry['b_cust_id']         = $mid;
            if($isNew)
            {
                $upAry['aproc']         = 'O';
            }

        } else {
            $upAry['isClose']           = 'Y';
        }
//        dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createSupplyMemberIdentity($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyMemberIdentity($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_ei',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?mid='.$request->urlid);
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
    public function create(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $isIns = 1;
        $js = $contents = '';
        $linceseAry = [];
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $mid       = SHCSLib::decode($urlid);
        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        }
        $typeAry    = b_supply_engineering_identity::getSelect();
        $memberAry  = b_supply_member::getSelect($supply_id);
        $identityAry= b_supply_engineering_identity::getMemberSelect(2,$mid);
        //工程身份
        if($request->type_id)
        {
            $user        = isset($memberAry[$request->b_cust_id])? $memberAry[$request->b_cust_id] : '';
            $identity    = isset($typeAry[$request->type_id])? $typeAry[$request->type_id] : '';
            //所需要的證照
            $linceseAry  = b_supply_engineering_identity_a::getSelect($request->type_id,0);
        }

        //view元件參數
        $postHref   = (!$request->type_id)?  $this->routerPost2 : $this->routerPost;
        $btnName    = (!$request->type_id)?  'btn_37' : 'btn_7';
        $hrefBack   = (!$request->license_id && $request->type_id)?  $this->hrefMainNew.$urlid : $this->hrefMain.'?mid='.$urlid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($postHref,$urlid),'POST',1,TRUE);


        //承攬商
        $html = b_supply::getName($supply_id);
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));

        //第一步驟：選擇人員＆工程身份
        if(!$request->b_cust_id || !$request->type_id)
        {
            //成員姓名
            $html = User::getName($mid);
            $form->add('nameT1', $html,Lang::get($this->langText.'.supply_53'));
            //工程身份
            $html = $form->select('type_id',$identityAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_51'),1);
        }
        //第二步驟：選擇 證照
        elseif(!$request->license_id && count($linceseAry))
        {
            //成員姓名
            $html = $user;
            $form->add('nameT1', $html,Lang::get($this->langText.'.supply_53'));
            //工程身份
            $html = HtmlLib::Color(Lang::get($this->langText.'.supply_72',['name'=>$identity]),'red',1);
            $html.= $form->hidden('type_id',$request->type_id);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_51'),1);

            foreach ($linceseAry as $lid => $lname)
            {
                list($show_name1,$show_name2,$show_name3,$show_name4,$show_name5,$edate_type) = e_license::getShowList($lid);
                $form->addHr();
                //證件
                $html = HtmlLib::Color($lname,'blue',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
                //
                $html = $form->text('license_id['.$lid.'][license_code]');
                $form->add('nameT3', $html,$show_name1,1);
                //有效期限
                $html = $form->date('license_id['.$lid.'][edate]',date('Y-m-d'),4,'','datetype');
                $html.= $form->hidden('license_id['.$lid.'][edate_type]',$edate_type);
                $html.= Lang::get($this->langText.'.supply_69');
                $form->add('nameT3', $html,$show_name2,1);
                //證明1－檔案1
                $html = $form->file('license_id['.$lid.'][file1]');
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);
                //證明1－檔案2
                //$html = $form->file('license_id['.$lid.'][file2]');
                //$form->add('nameT3', $html,Lang::get($this->langText.'.supply_35'));
                //證明1－檔案3
                //$html = $form->file('license_id['.$lid.'][file3]');
                //$form->add('nameT3', $html,Lang::get($this->langText.'.supply_36'));
            }
        } elseif(!$request->license_id && !count($linceseAry))
        {
            //成員姓名
            $html = $user;
            $form->add('nameT1', $html,Lang::get($this->langText.'.supply_53'));
            //工程身份
            $html = HtmlLib::Color(Lang::get($this->langText.'.supply_78',['name'=>$identity]),'red',1);
            $html.= $form->hidden('type_id',$request->type_id);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_51'),1);
            $isIns = 0;
        }

        //Submit
        $submitDiv  = '';
        if($isIns)
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.'.$btnName),'1','agreeY').'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('b_supply_id',$supply_id);
        $submitDiv.= $form->hidden('urlid',$urlid);
        $submitDiv.= $form->hidden('b_cust_id',$mid);
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
            $("form").submit(function() {
                      $(this).find("input[type=\'submit\']").prop("disabled",true);
                    });


            $(".datetype").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
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
