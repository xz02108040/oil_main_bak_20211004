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
use App\Http\Traits\Factory\CarTrait;
use App\Model\Supply\b_supply_rp_car;
use App\Model\Engineering\e_project_car;
use App\Http\Traits\Supply\SupplyRPCarTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;

class SupplyCarController extends Controller
{
    use SupplyRPCarTrait,CarTrait,SessTraits,EngineeringCarTrait;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPCarController
    |--------------------------------------------------------------------------
    |
    | 承攬商車輛管理
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
        $this->hrefMain         = 'contractorcar';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'contractorcar/';
        $this->hrefMainNew      = 'new_contractorcar';
        $this->routerPost       = 'postContractorcar';

        $this->pageTitleMain    = Lang::get($this->langText.'.title10');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list10');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new10');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit10');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','1024000');
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
        $this->isWirte = ($this->isRootDept == 'Y' || $this->isRoot == 'Y')? 'Y' : 'N';//SHCSLib::checkUriWrite($this->uri);
        //參數
        $no = 0;
        $out = $js = $supply = '';
        $closeAry = SHCSLib::getCode('CLOSE');
        $typeAry  = b_car_type::getSelect();
        //承攬商
        $pid      = SHCSLib::decode($request->pid);
        $aid      = $request->aid;
        $cid      = $request->cid;
        $bid      = $request->bid;
        $sdate    = $request->sdate;
        $edate    = $request->edate;
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        if($request->has('clear'))
        {
            $aid= $cid = 0;
            $bid = $sdate = $edate = '';
            Session::forget($this->hrefMain.'.search');
        }

        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid','');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }

        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','N');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$sdate && !is_null($sdate))
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate','');
        } else {
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate && !is_null($edate))
        {
            $edate = Session::get($this->hrefMain.'.search.edate','');
        } else {
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $searchList = [$aid,$cid,$bid,$sdate,$edate];
        if(!$pid)
        {
            $listAry = $this->getApiSupplyCarMainList($searchList);

        } else {
            $listAry = $this->getApiCarList(2,$pid,$searchList);
//            dd($listAry);
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

        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.supply_102'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.supply_103'));
        $form->addRowCnt($html);
        $html = $form->select('aid',$typeAry,$aid,2,Lang::get($this->langText.'.supply_42'));
        $html.= $form->text('bid',$bid,2,Lang::get($this->langText.'.supply_41'));
        $html.= $form->select('cid',$closeAry,$cid,2,Lang::get($this->langText.'.supply_7'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = $form->memo(HtmlLib::Color(Lang::get($this->langText.'.supply_1046'),'red',1));
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
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_9')]; //電話
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_41')]; //車牌
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_114')]; //通行證號
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_42')]; //車輛分類
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_87')]; //發證日
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_88')]; //上次驗車日
            //[] = ['title'=>Lang::get($this->langText.'.supply_89')]; //下次驗車日
            //$heads[] = ['title'=>Lang::get($this->langText.'.supply_90')]; //驗車狀態
            //$heads[] = ['title'=>Lang::get($this->langText.'.supply_95')]; //上次驗排氣日
            //$heads[] = ['title'=>Lang::get($this->langText.'.supply_96')]; //下次驗排氣日
            //$heads[] = ['title'=>Lang::get($this->langText.'.supply_97')]; //驗排氣狀態
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_7')]; //狀態
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
                    $name13       = $value->car_memo; //
                    $name14       = $value->project; //
                    $name3        = $value->car_type_name; //
                    $name4        = $value->sdate; //
                    $name5        = $value->last_car_inspection_date; //
                    $name6        = $value->last_car_inspection_date2; //
                    $name6Color   = (CheckLib::isDate($value->last_car_inspection_date2) && SHCSLib::getBetweenDays($value->last_car_inspection_date2) <= 30) ? 5 : 2 ; //停用顏色
                    $name7        = $value->inspection_name1; //
                    $name7Color   = ($value->isInspectionCar == 'N') ? 5 : 2 ;
                    $name10       = ($value->oil_kind == 2)? $value->last_exhaust_inspection_date : ''; //
                    $name11       = $value->last_exhaust_inspection_date2; //
                    $name11Color   = (CheckLib::isDate($value->last_exhaust_inspection_date2) && SHCSLib::getBetweenDays($value->last_exhaust_inspection_date2) <= 30) ? 5 : 2 ; //停用顏色
                    $name12       = $value->inspection_name2; //
                    $name12Color   = ($value->isInspectionExhaust == 'N') ? 5 : 2 ;
                    $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                    $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                    //按鈕
                    $btn          = ($this->isWirte == 'Y' && $value->isClose == 'N')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,($id)),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '2'=>[ 'name'=> $name2],
                        '13'=>[ 'name'=> $name13],
                        '14'=>[ 'name'=> $name14],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '90'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
                    $("#sdate,#edate,#last_car_inspection_date,#last_exhaust_inspection_date").datepicker({
                        format: "yyyy-mm-dd",
                        changeYear: true, 
                        language: "zh-TW"
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js   = $contents = '';
        $id   = SHCSLib::decode($urlid);
        $supplyAry      = b_supply::getSelect();
        $typeAry        = b_car_type::getSelect();
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
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            $pid        = $getData->b_supply_id; //
            //資料明細
            $A3         = $getData->supply; //
            $A4         = $getData->img_path; //
            $A5         = $getData->filePath1; //
            $A6         = $getData->filePath2; //
            $A7         = $getData->filePath3; //
            $A8         = $getData->oil_kind; //

            $A11        = $getData->car_no; //
            $A12        = $getData->car_memo; //
            $A13        = $getData->car_type; //
            $A14        = $getData->sdate; //
            $A15        = $getData->last_car_inspection_date; //
            if($A15 == '1900-01-01') $A15 = '';
            $A16        = $getData->last_car_inspection_date2; //
            $A17        = $getData->inspection_name1; //
            $A20        = $getData->last_exhaust_inspection_date; //
            if($A20 == '1900-01-01') $A20 = '';
            $A21        = $getData->last_exhaust_inspection_date2; //
            $A22        = $getData->inspection_name2; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);

        //承攬商
        $html = $A3;
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_12'),1);
        //車牌
        $html = $form->text('car_no',$A11);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_41'),1);
        //車牌
        $html = $form->text('car_memo',$A12);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_114'),1);
        //車輛分類
        $html = $form->select('car_type',$typeAry,$A13);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_42'),1);
        //發證日
        $html = $form->date('sdate',$A14);
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1047'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_87'));
        //上次驗車日
        $html = $form->date('last_car_inspection_date',$A15);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_88'));
        //下次驗車日
//        $html = $A16;
//        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_89'));
        if($A8 == 2)
        {
            //上次驗車日
            $html = $form->date('last_exhaust_inspection_date',$A20);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_95'));
        }

        //下次驗車日
//        $html = $A21;
//        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_96'));
        //車輛照片
        $html    = ($A4)? Html::image($A4,'',['class'=>'img-responsive','height'=>'30%']) : '';
        $html   .= $form->file('img_path');
        $html   .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="240" /></span>';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_44'),1);

        //證明1－檔案1
        $html  = ($A5)? $form->linkbtn($A5, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file1','',2);
        $html .= '<span id="blah_div1" style="display: none;"><img id="blah1" src="#" alt="" width="240" /></span>';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_115'),1);
        //證明1－檔案2
        $html  = ($A6)? $form->linkbtn($A6, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file2','',2);
        $html .= '<span id="blah_div2" style="display: none;"><img id="blah2" src="#" alt="" width="240" /></span>';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_35'));
        //證明1－檔案3
        $html  = ($A7)? $form->linkbtn($A7, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file3','',2);
        $html .= '<span id="blah_div3" style="display: none;"><img id="blah3" src="#" alt="" width="240" /></span>';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_36'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.supply_18'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->hidden('b_supply_id',$pid);
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
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
            $("#sdate,#edate,#last_car_inspection_date,#last_exhaust_inspection_date").datepicker({
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
        //讀取 Session 參數
        $this->getBcustParam();

        //資料不齊全
        //增加判斷，直接勾選停用時，忽略必填欄位，直接停用該筆資料
        if(($this->isRoot == 'N') && (!$request->id || !$request->b_supply_id || !$request->car_no || !$request->car_memo || !$request->car_type) && $request->isClose !='Y')
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }//重複
        elseif(b_car::isExist($request->car_no,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1010'))
                ->withInput();
        }
        elseif($request->sdate && !CheckLib::isDate($request->sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->sdate && strtotime($request->sdate) > time())
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        elseif($request->last_car_inspection_date && strtotime($request->last_car_inspection_date) > time())
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        elseif($request->last_exhaust_inspection_date && strtotime($request->last_exhaust_inspection_date) > time())
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1027'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $file1 = $file1N = $file2 = $file2N = $file3 = $file3N = '';
        $filepath = $filename = '';


        //處理圖片
        if($request->hasFile('img_path'))
        {
            //人頭像比例[車輛比照]
            $head_max_height = sys_param::getParam('USER_HEAD_HEIGHT',640);
            $head_max_width  = sys_param::getParam('SER_HEAD_WIDTH',360);
            $ImgFile    = $request->img_path;
            $extension  = $ImgFile->extension();
            $filesize   = $ImgFile->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','GIF'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_imgmemo'))
                    ->withInput();
            } elseif($filesize > $this->fileSizeLimit1) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                //圖片位置
                $filepath = config('mycfg.car_head_path').date('Y/m/');
                $filename = $request->car_no.'_head.'.$extension;
                $imagedata = file_get_contents($ImgFile);
                Storage::put($filepath.$filename,$imagedata);
            }
        }
        //檔案1
        if($request->hasFile('file1'))
        {
            $File       = $request->file1;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','PDF','GIF'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_imgmemo2'))
                    ->withInput();
            } elseif($filesize > $this->fileSizeLimit1) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file1N = $extension;
                $file1  = file_get_contents($File);
            }
        }
        //檔案2
        if($request->hasFile('file2'))
        {
            $File       = $request->file2;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','PDF','GIF'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_imgmemo2'))
                    ->withInput();
            }  elseif($filesize > $this->fileSizeLimit1) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file2N = $extension;
                $file2  = file_get_contents($File);
            }
        }
        //檔案3
        if($request->hasFile('file3'))
        {
            $File       = $request->file3;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','PDF','GIF'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_imgmemo2'))
                    ->withInput();
            } elseif($filesize > $this->fileSizeLimit1) {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file3N = $extension;
                $file3  = file_get_contents($File);
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['car_kind']          = 2; //承攬商
        $upAry['car_no']            = strtoupper($request->car_no);
        $upAry['car_memo']          = $request->car_memo;
        $upAry['car_type']          = $request->car_type;
        $upAry['sdate']             = $request->sdate;
        $upAry['edate']             = '9999-12-31'; //2020-11-16 大林版本 車證允許永久
        $upAry['last_car_inspection_date']      = $request->last_car_inspection_date;
        $upAry['last_exhaust_inspection_date']  = $request->last_exhaust_inspection_date;
        $upAry['img_path']          = $filepath.$filename;
        $upAry['file1']             = $file1;
        $upAry['file1N']            = $file1N;
        $upAry['file2']             = $file2;
        $upAry['file2N']            = $file2N;
        $upAry['file3']             = $file3;
        $upAry['file3N']            = $file3N;
        $upAry['b_supply_id']       = $request->b_supply_id;
        if($request->isClose)
        {
            $upAry['isClose']       = 'Y';
        }

        //新增
        if($isNew)
        {
            $ret = $this->createCar($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setCar($id, $upAry, $this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_car',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.SHCSLib::encode($request->b_supply_id));
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
    public function create()
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
        $supplyAry      = b_supply::getSelect();
        $typeAry        = b_car_type::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //--- 車輛資訊區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_11147'),3);
        $form->addHtml( $html );

        //承攬商
        $html = $form->select('b_supply_id',$supplyAry,1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_12'),1);
        //車牌
        $html = $form->text('car_no','');
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_41'),1);
        //車牌
        $html = $form->text('car_no','');
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_41'),1);
        //車輛分類
        $html = $form->select('car_type',$typeAry,1);
        $form->add('nameT2', $html,Lang::get('sys_supply.supply_42'),1);
        //有效日期
        $html = $form->date('sdate');
        $form->add('nameT3', $html,Lang::get('sys_supply.發證日'),1);
        //上次驗車日
        $html = $form->date('last_inspection_date');
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_88'),1);
        //車輛照片
        $html    = $form->file('img_path');
        $html   .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="240" /></span>';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_44'),1);


        //行照－檔案1
        $html = $form->file('file1');
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_115'));
        //行照－檔案2
        $html = $form->file('file2');
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_35'));
        //行照－檔案3
        $html = $form->file('file3');
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_36'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_11'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
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
