<?php

namespace App\Http\Controllers\Supply;

use Auth;
use Html;
use Lang;
use Session;
use Storage;
use App\Lib\LogLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use App\Model\sys_param;
use App\Model\Factory\b_car;
use Illuminate\Http\Request;
use App\Model\Supply\b_supply;
use App\Http\Traits\SessTraits;
use App\Model\Factory\b_car_type;
use App\Http\Controllers\Controller;
use App\Model\Engineering\e_project;
use App\Http\Traits\Factory\CarTrait;
use App\Model\Engineering\e_project_car;
use App\Http\Traits\Supply\SupplyRPCarTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;

class SupplyRPCarController extends Controller
{
    use SupplyRPCarTrait,CarTrait,EngineeringCarTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPCarController
    |--------------------------------------------------------------------------
    |
    | 承攬商車輛申請單
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
        $this->hrefHome         = 'contractor';
        $this->hrefMain         = 'rp_contractorcar';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractorcar/';
        $this->hrefMainDetail2  = 'rp_contractorcar2/';
        $this->hrefMainNew      = 'new_rp_contractorcar';
        $this->routerPost       = 'postContractorrpcar';

        $this->pageTitleMain    = Lang::get($this->langText.'.title11');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list11');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new11');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit11');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
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
        //允許管理的工程案件
        $allowProjectAry= $this->allowProjectAry;
        //參數
        $no = 0;
        $out = $js = $supply = '';
        $aprocColorAry  = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',1);
        //進度
        $aproc    = ($request->aproc)? $request->aproc : '';
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc',($this->isRootDept? 'P' : 'A'));
        }
        //工安課審查階段
        $allowProjectAry = ($this->isRootDept)? [] : $allowProjectAry;
        //承攬商
        $pid      = SHCSLib::decode($request->pid);
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$pid)
        {
            $listAry = $this->getApiSupplyRPCarMainList($aproc,$allowProjectAry);

        } else {
            $listAry = $this->getApiSupplyRPCarList($pid,$aproc,$allowProjectAry);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //搜尋
        $html = $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'No'];
        if(!$pid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_12')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_9')];  //電話
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_126')]; //申請類型
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_41')]; //車牌
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_114')]; //通行證號
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_42')]; //車輛分類
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_87')]; //發證日
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_88')]; //上次驗車日
            //$heads[] = ['title'=>Lang::get($this->langText.'.supply_95')]; //上次驗排氣日
        }

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                if(!$pid)
                {
                    $id           = $value->b_supply_id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->tel1; //
                    $name3        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name2        = $value->car_no; //
                    $name3        = $value->car_type_name; //
                    $name4        = $value->sdate; //
                    $name5        = $value->last_car_inspection_date; //
                    $name6        = $value->last_exhaust_inspection_date; //
                    if($name5 == '1900-01-01') $name5 = '';

                    $name7        = HtmlLib::Color($value->supply,'',1).'<br/>'.$value->apply_name.'<br/>'.$value->apply_stamp; //
                    $name8        = $value->project_no.'<br/>'.$value->project; //
                    $name9        = $value->aproc_name; //
                    $name10       = $value->car_memo; //
                    $name13       = $value->rp_type_name; //
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //
                    $isCharge     = ($this->isRootDept && $aproc == 'P')? 1 : ((!$this->isRootDept && $aproc == 'A')? 1 : 0);

                    //按鈕
                    $btnRoute     = ($isCharge)? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($isCharge)? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $name7],
                        '12'=>[ 'name'=> $name8],
                        '13'=>[ 'name'=> $name13],
                        '21'=>[ 'name'=> $name9,'label'=>$aprocColor],
                        '2'=>[ 'name'=> $name2],
                        '10'=>[ 'name'=> $name10],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '99'=>[ 'name'=> $btn ]
                    ];
                }

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
     * 單筆資料 新增
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        $typeAry  = b_car_type::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            $pid        = $getData->b_supply_id; //
            //資料明細
            $A1         = $getData->apply_name; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->aproc; //

            $A5         = $getData->filePath1; //
            $A6         = $getData->filePath2; //
            $A7         = $getData->filePath3; //
            $A8         = $getData->img_path; //
            $A9         = $getData->supply; //

            $A10        = $getData->project_no.' '.$getData->project; //
            $A11        = $getData->car_no; //
            $A13        = $getData->car_type_name; //
            $A14        = $getData->sdate; //
            $A15        = $getData->last_car_inspection_date; //
            if($A15 == '1900-01-01') $A15 = '';

            $A16        = $getData->last_exhaust_inspection_date; //
            if($A16 == '1900-01-01') $A16 = '';
            $A17        = $getData->last_exhaust_inspection_date2; //
            $A18        = $getData->car_memo;
            $A19        = b_car_type::getOilKind($getData->car_type);

            $A20        = $getData->project_edate; //
            $A21        = $getData->rp_type; //
            $form_color = $this->form_color($A21); //
            $rp_type_Ary= $this->getCarRpTypeAry($A21);

            $A23        = $getData->charge_name1; //
            $A24        = $getData->charge_stamp1; //
            $A25        = $getData->charge_memo1; //

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1.' / '.HtmlLib::Color($A9,'',1);
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //申請類型
        $html = $form->select('rp_type',$rp_type_Ary,$A21);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_126'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //監造審查
        if($A23)
        {
            $html  = $A23;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_83'),1);
            $html  = $A24;
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
            $html  = HtmlLib::Color($A25,'',1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
        }
        //工程案件
        $html = $A10;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'),1);

        //車牌
        $html = $A11.$form->hidden('car_no',$A11);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_41'),1);

        if ($A4 == 'P') {
            //發行證號
            if (in_array($A21, [1, 2])) {
                $html = $form->text('car_memo', $A18);
            } else {
                $html = $A18;
            }
            $form->add('nameT2', $html, Lang::get($this->langText . '.supply_114'), 1);
        }
        //申請加公司及案件
        if (in_array($A21, [1,2])) {
            //車輛分類
            $html = $A13;
            $form->add('nameT2', $html, Lang::get($this->langText . '.supply_42'), 1);
            //發證日
            $html = ($A4 == 'P') ? $form->date('sdate', $A14) : $A14;
            $form->add('nameT3', $html, Lang::get($this->langText . '.supply_87'), 1);
            //上次驗車日
            $html = ($A4 == 'P') ? $form->date('last_car_inspection_date', $A15) . HtmlLib::Color(Lang::get('sys_supply.supply_1027'), 'red', 1) : $A15;
            $form->add('nameT3', $html, Lang::get($this->langText . '.supply_88'));
            if ($A19 == 2) {
                //上次驗排氣日
                $html = ($A4 == 'P') ? $form->date('last_exhaust_inspection_date', $A16) . HtmlLib::Color(Lang::get('sys_supply.supply_1027'), 'red', 1) : $A16;
                $form->add('nameT3', $html, Lang::get($this->langText . '.supply_95'));
            }
            //下次驗排氣日
            //        $html = ($A4 == 'P')? $form->date('last_exhaust_inspection_date2',$A17).HtmlLib::Color(Lang::get('sys_supply.supply_1006'),'red',1) : $A17;
            //        $form->add('nameT3', $html,Lang::get('sys_supply.supply_96'),1);
            if ($A4 == 'P') {
                $today = date('Y-m-d');
                $html = $form->date('door_sdate', $today);
                $form->add('nameT3', $html, Lang::get('sys_supply.supply_98'), 1);
                $html = $form->date('door_edate', $A20);
                $form->add('nameT3', $html, Lang::get('sys_supply.supply_99'), 1);
            }

            //車輛照片
            $html    = ($A8) ? Html::image($A8, '', ['class' => 'img-responsive', 'height' => '30%']) : '';
            $form->add('nameT3', $html, Lang::get('sys_supply.supply_44'));

            //行照－檔案1
            $html  = ($A5) ? $form->linkbtn($A5, Lang::get('sys_btn.btn_29'), 4, '', '', '', '_blank') : '';
            $form->add('nameT3', $html, Lang::get('sys_supply.supply_115'), 1);
            //行照－檔案2
            $html  = ($A6) ? $form->linkbtn($A6, Lang::get('sys_btn.btn_29'), 4, '', '', '', '_blank') : '';
            $form->add('nameT3', $html, Lang::get('sys_supply.supply_35'));
            //行照－檔案3
            $html  = ($A7) ? $form->linkbtn($A7, Lang::get('sys_btn.btn_29'), 4, '', '', '', '_blank') : '';
            $form->add('nameT3', $html, Lang::get('sys_supply.supply_36'));
        }
        $form->addHr();
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY','','chgSubmit("agreeY")').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN','','chgSubmit("agreeN")').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('aproc',$A4);
        $submitDiv.= $form->hidden('project_edate',$A20);
        $submitDiv.= $form->hidden('submitBtn','');
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out, $form_color));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("form").submit(function() {
                      $(this).find("input[type=\'submit\']").prop("disabled",true);
            });
            $("#sdate,#door_sdate,#door_edate,#last_car_inspection_date,#last_exhaust_inspection_date,#last_exhaust_inspection_date2").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            $("input[name=\'img_path\']").change(function() {
              readURL(this,"#blah","#blah_div");
              $("#blah_div").hide();
            });
            $("input[name=\'file1\']").change(function() {
              readURL(this,"#blah1","#blah_div1");
              $("#blah_div").hide();
            });
            $("input[name=\'file2\']").change(function() {
              readURL(this,"#blah2","#blah_div2");
              $("#blah_div").hide();
            });
            $("input[name=\'file3\']").change(function() {
              readURL(this,"#blah3","#blah_div3");
              $("#blah_div").hide();
            });
            function readURL(input,divblah,divshow) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $(divblah).attr("src", e.target.result);
                  $(divshow).show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
        });
        function chgSubmit(btnTitle)
        {
            $("#submitBtn").val(btnTitle);
        }
        ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 單筆資料 新增
     */
    public function show2(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageEditTitle; //table header

        //資料內容
        $getData    = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            $pid        = $getData->b_supply_id; //
            //資料明細
            $A1         = $getData->apply_name; //
            $A2         = $getData->apply_stamp; //
            $A3         = $getData->aproc_name; //
            $A4         = $getData->aproc; //

            $A5         = $getData->filePath1; //
            $A6         = $getData->filePath2; //
            $A7         = $getData->filePath3; //
            $A8         = $getData->img_path; //
            $A9         = $getData->supply; //

            $A10        = $getData->project; //
            $A11        = $getData->car_no; //
            $A13        = $getData->car_type_name; //
            $A14        = $getData->sdate; //
            $A15        = $getData->last_car_inspection_date; //
            if($A15 == '1900-01-01') $A15 = '';
            $A16        = $getData->last_exhaust_inspection_date; //
            if($A16 == '1900-01-01') $A16 = '';
            $A18        = $getData->car_memo; //


            $A21        = $getData->charge_name1; //
            $A22        = $getData->charge_stamp1; //
            $A23        = $getData->charge_memo1; //

            $A31        = $getData->charge_name2; //
            $A32        = $getData->charge_stamp2; //
            $A33        = $getData->charge_memo2; //

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //申請人
        $html = $A1.' / '.HtmlLib::Color($A9,'',1);
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));


        //工程案件
        $html = $A10;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_49'),1);
        //車牌
        $html = $A11;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_41'),1);
        //車牌
        $html = $A18;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_114'),1);
        //車輛分類
        $html = $A13;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_42'),1);
        //發證日
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_87'),1);
        //上次驗車日
        $html = $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_88'));
        //上次驗排氣日
        $html = $A16;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_95'));

        //車輛照片
        $html    = ($A8)? Html::image($A8,'',['class'=>'img-responsive','height'=>'30%']) : '';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_44'));

        //行照－檔案1
        $html  = ($A5)? $form->linkbtn($A5, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_115'),1);
        //行照－檔案2
        $html  = ($A6)? $form->linkbtn($A6, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_35'));
        //行照－檔案3
        $html  = ($A7)? $form->linkbtn($A7, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_36'));

        if($A4 != 'A')
        {
            $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_82'),4);
            $form->addHtml( $html );
            //監造審查
            if($A21)
            {
                $html  = $A21;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_83'),1);
                $html  = $A22;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A23,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //工安審查
            if($A31)
            {
                $html  = $A31;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_86'),1);
                $html  = $A32;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A33,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('b_supply_id',$pid);
        $submitDiv.= $form->hidden('pid',$pid);
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
            $("#edate").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            $("input[name=\'img_path\']").change(function() {
              readURL(this,"#blah","#blah_div");
              $("#blah_div").hide();
            });
            $("input[name=\'file1\']").change(function() {
              readURL(this,"#blah1","#blah_div1");
              $("#blah_div").hide();
            });
            $("input[name=\'file2\']").change(function() {
              readURL(this,"#blah2","#blah_div2");
              $("#blah_div").hide();
            });
            $("input[name=\'file3\']").change(function() {
              readURL(this,"#blah3","#blah_div3");
              $("#blah_div").hide();
            });
            function readURL(input,divblah,divshow) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $(divblah).attr("src", e.target.result);
                  $(divshow).show();
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        /**
         * 第一階段：規則檢核
         */
        //1-1. 取得「事件」按鈕
        $submitBtn = $request->submitBtn;
        $today          = date('Y-m-d');
        $todayStamp     = strtotime($today);
        //1-2-1. 規則檢核：資料異常，請重新作業，謝謝
        if(!$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10019'))
                ->withInput();
        }elseif($request->aproc == 'P' && $submitBtn == 'agreeY' && !$request->car_memo && in_array($request->rp_type, [1, 2]))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1051',['name'=>Lang::get('sys_supply.supply_114')]))
                ->withInput();
        }elseif($request->sdate && !CheckLib::isDate($request->sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_87').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->sdate && strtotime($request->sdate) > $todayStamp)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_87').'，'.Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        elseif($request->last_car_inspection_date && !CheckLib::isDate($request->last_car_inspection_date))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_88').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->last_car_inspection_date && strtotime($request->last_car_inspection_date) > $todayStamp)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_88').'，'.Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        elseif($request->last_exhaust_inspection_date && !CheckLib::isDate($request->last_exhaust_inspection_date))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_95').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->last_exhaust_inspection_date && strtotime($request->last_exhaust_inspection_date) > $todayStamp)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_95').'，'.Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        elseif($request->last_exhaust_inspection_date2 && !CheckLib::isDate($request->last_exhaust_inspection_date2))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_96').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->last_exhaust_inspection_date2 && strtotime($request->last_exhaust_inspection_date2) < $todayStamp)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_96').'，'.Lang::get('sys_supply.supply_1006'))
                ->withInput();
        }
        elseif($request->door_sdate && !CheckLib::isDate($request->door_sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_98').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->door_edate && !CheckLib::isDate($request->door_edate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_99').'，'.Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->door_edate && strtotime($request->door_edate) < strtotime($request->door_sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_99').'，'.Lang::get('sys_supply.supply_1049'))
                ->withInput();
        }
        elseif($request->door_sdate && $request->door_edate && strtotime($request->door_edate) < strtotime($request->door_sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1036'))
                ->withInput();
        }
        elseif($request->door_edate && strtotime($request->door_edate) > strtotime($request->project_edate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1038',['date'=>$request->project_edate]))
                ->withInput();
        }
        /**
         * 第二階段：參數
         */
        $this->getBcustParam();
        $pid  = $request->b_supply_id;
        $id   = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;

        if(!$pid && is_numeric($pid) && $pid > 0)
        {
            $msg = Lang::get($this->langText.'.supply_1000');
            return \Redirect::back()->withErrors($msg);
        }
        $isNew  = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $file1  = $file1N = $file2 = $file2N = $file3 = $file3N = '';
        $isAgree= 0;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['b_supply_id']       = $pid;
        $upAry['charge_memo']       = strlen($request->charge_memo)? $request->charge_memo : '';
        /**
         * 第三階段：同意
         */
        if ($submitBtn == 'agreeY')
        {   
            //申請類型 1.加公司加案件 2.只加案件  4.只退案件 5.退公司退案件
            switch ($request->rp_type) {
                // 1.檢查車牌和案件
                case '1':
                    //車輛牌照號碼已存在！
                    if (b_car::isExist($request->car_no, $id) && e_project_car::isCarNoExist($request->car_no)) {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_supply.supply_1010'))
                            ->withInput();
                    }
                    break;

                // 2.只檢查案件
                case '2':
                    //該車輛已加入該工程案件內!
                    if(e_project_car::isCarNoExist($request->car_no))
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_supply.supply_1035'))
                            ->withInput();
                    } 
                    break;

                // 3.轉換公司
                case '3':
                    $isCheck = 0;
                    break;

                // 4.只退案件
                case '4':
                    //該車輛不在此案件內!
                    if(!e_project_car::isCarNoExist($request->car_no))
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_supply.supply_1072'))
                            ->withInput();
                    } 
                    break;

                 // 5.退公司退案件
                case '5':
                    //該車輛不在此案件內!
                    if(!e_project_car::isCarNoExist($request->car_no))
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_supply.supply_1072'))
                            ->withInput();
                    } 
                    //該車輛不存在！
                    elseif(!b_car::isExist($request->car_no, $id))
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_supply.supply_1073'))
                            ->withInput();
                    }
                    break;
            }

            $isAgree        = 1;
            $upAry['rp_type']               = $request->rp_type;
            $upAry['car_kind']              = 2;
            $upAry['aproc']                 = ($request->aproc == 'A') ? 'P' : 'O';
            $upAry['b_supply_rp_car_id']    = $id;
            $upAry['car_memo']                      = isset($request->car_memo) ? $request->car_memo : '';
            $upAry['door_sdate']                    = isset($request->door_sdate) ? $request->door_sdate : '';
            $upAry['door_edate']                    = isset($request->door_edate) ? $request->door_edate : '';
            $upAry['sdate']                         = isset($request->sdate) ? $request->sdate : '';
            $upAry['last_car_inspection_date']      = isset($request->last_car_inspection_date) ? $request->last_car_inspection_date : '';
            $upAry['last_exhaust_inspection_date']  = isset($request->last_exhaust_inspection_date) ? $request->last_exhaust_inspection_date : '';
            $upAry['last_exhaust_inspection_date2'] = isset($request->last_exhaust_inspection_date2) ? $request->last_exhaust_inspection_date2 : '';
        }
        // dd($upAry,$request->all());
        
        /**
         * 第三階段：不同意
         */
        if ($submitBtn == 'agreeN')
        {
            if (!$request->charge_memo)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1021'))
                    ->withInput();
            } else {
                $upAry['aproc']         = 'C';
            }
        }
        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPCar($upAry,$this->b_cust_id);
            $ret = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPCar($id,$upAry,$this->b_cust_id);
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
                if($isAgree)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_car',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_car',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
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

    /**
     * 取得 指定form的顏色
     * @param int $rp_type
     * @return int
     */
    protected function form_color($rp_type = 0)
    {
        if (in_array($rp_type, [1, 2])) {
            $form_color = 2;
        } elseif (in_array($rp_type, [3])) {
            $form_color = 3;
        } else {
            $form_color = 4;
        }
        return $form_color;
    }
}
