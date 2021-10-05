<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Factory\CarTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_car;
use App\Model\Supply\b_supply;
use App\Model\Factory\b_car;
use App\Model\Factory\b_car_type;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;
use Storage;

class EngineeringCarBuildController extends Controller
{
    use EngineeringCarTrait,CarTrait,SessTraits;
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
        $this->hrefMain         = 'buildcar';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'buildcar/';
        $this->hrefMainNew      = 'new_buildcar';
        $this->routerPost       = 'postBuildcar';
        $this->routerPost2      = 'buildCarList';

        $this->pageTitleMain    = Lang::get($this->langText.'.title27');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list27');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new27');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit27');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

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
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $no = 0;
        $out = $js = $supply = '';
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        $e_project_id = $request->e_project_id;
        $b_supply_id  = ($e_project_id)? e_project::getSupply($e_project_id) : 0;
        //view元件參數
        $tbTitle    = $this->pageTitleList;//列表標題
        $routePost  = ($e_project_id)?  $this->routerPost : $this->routerPost2;
        $btnPost    = ($e_project_id)?  'btn_8' : 'btn_37';
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($routePost,-1),'POST',1,TRUE);
        //--- 車輛資訊區 ---//
        $html = HtmlLib::genBoxStart(Lang::get('sys_base.base_11147'),3);
        $form->addHtml( $html );

        //工程案件
        if(!$e_project_id){
            //工程案件
            $projectAry = e_project::getActiveProjectSelect();
            $html = $form->select('e_project_id',$projectAry);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_49'),1);
        }else{
            //工程案件
            $html = e_project::getName($e_project_id);
            $html.= $form->hidden('e_project_id',$e_project_id);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_49'),1);

            //承攬商
            $html = b_supply::getName($b_supply_id);
            $html.= $form->hidden('b_supply_id',$b_supply_id);;
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_12'),1);
            //選擇器
            $html = $form->select('select_car',[1=>Lang::get('sys_supply.supply_91'),2=>Lang::get('sys_supply.supply_92')],1);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_93'),1);

            //車牌
            $carAry = b_car::getSelect(0,$b_supply_id);
            $form->addHtml('<div id="show1">');
            $html = $form->select('b_car_id',$carAry);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_91'),1);
            $form->addHtml('</div>');

            //自建車體
            $form->addHtml('<div id="show2" style="display: none;">');
            $today      = date('Y-m-d');
            $typeAry    = b_car_type::getSelect();
            //車牌
            $html = $form->text('car_no','');
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_41'),1);
            //車輛分類
            $html = $form->select('car_type',$typeAry,1);
            $form->add('nameT2', $html,Lang::get('sys_supply.supply_42'),1);
            //發證日
            $html = $form->date('sdate',$today);
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_87'),1);
            //上次驗車日
            $html = $form->date('last_car_inspection_date',$today);
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_88'),1);
            //上次驗排氣日
            $html = $form->date('last_exhaust_inspection_date',$today);
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_95'),1);
            //車輛照片
            $html    = $form->file('img_path');
            $html   .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="240" /></span>';
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_44'));

            //證明1－檔案1
            $html  = $form->file('file1');
            $html .= '<span id="blah_div1" style="display: none;"><img id="blah1" src="#" alt="" width="240" /></span>';
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_34'),1);
            //證明1－檔案2
            $html = $form->file('file2');
            $html .= '<span id="blah_div2" style="display: none;"><img id="blah2" src="#" alt="" width="240" /></span>';
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_35'));
            //證明1－檔案3
            $html  = $form->file('file3');
            $html .= '<span id="blah_div3" style="display: none;"><img id="blah3" src="#" alt="" width="240" /></span>';
            $form->add('nameT3', $html,Lang::get('sys_supply.supply_36'));

            $form->addHtml('</div>');
        }

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$btnPost),'1','agreeY').'&nbsp;';

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $form->boxFoot($submitDiv);

        $out = $form->output();


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
                    
                    $("#sdate,#last_car_inspection_date").datepicker({
                        format: "yyyy-mm-dd",
                        changeYear: true, 
                        language: "zh-TW"
                    });
                    
                    $("#select_car").change(function() {
                      var select_car = $("#select_car").val();
                      if(select_car == 2)
                      {
                        $("#show1").hide();
                        $("#show2").show();
                       
                      } else {
                        $("#show1").show();
                        $("#show2").hide();
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
//        dd($request->all());
        //資料不齊全
        if(!$request->id || !$request->b_supply_id || !$request->e_project_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->select_car == 1 && !$request->b_car_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1033'))
                ->withInput();
        }//重複配對
        elseif($request->select_car == 1 && e_project_car::isExist($request->e_project_id,$request->b_car_id))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1035'))
                ->withInput();
        }
        elseif($request->select_car == 2 && (!$request->car_no || !$request->last_car_inspection_date || !$request->last_exhaust_inspection_date || !$request->sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1034'))
                ->withInput();
        }
        elseif($request->select_car == 2 && $request->sdate && !CheckLib::isDate($request->sdate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->select_car == 2 && $request->last_car_inspection_date && !CheckLib::isDate($request->last_car_inspection_date))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        elseif($request->select_car == 2 && $request->last_exhaust_inspection_date && !CheckLib::isDate($request->last_exhaust_inspection_date))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1004'))
                ->withInput();
        }
        //重複
        elseif($request->select_car == 2 && b_car::isExist($request->car_no))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1010'))
                ->withInput();
        }
        //未來日
        elseif($request->select_car == 2 && $request->sdate && strtotime($request->sdate) > strtotime(date('Y-m-d')))
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
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','GIF'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10119'))
                    ->withInput();
            } else {
                //圖片位置
                $filepath = config('mycfg.car_head_path').date('Y/m/');
                $filename = $request->car_no.'_head.'.$extension;
                $imagedata = file_get_contents($ImgFile);

                //轉換 圖片大小
                if(!SHCSLib::tranImgSize($filepath.$filename,$imagedata,$head_max_width,$head_max_height))
                {
                    $filepath = $filename = '';
                }
            }
        }
        //檔案1
        if($request->hasFile('file1'))
        {
            $File       = $request->file1;
            $extension  = $File->extension();
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
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
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
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
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
                    ->withInput();
            } else {
                $file3N = $extension;
                $file3  = file_get_contents($File);
            }
        }

        $upAry = array();
        $upAry['e_project_id']      = $request->e_project_id;
        $upAry['b_supply_id']       = $request->b_supply_id;

        //新增
        if($request->select_car == 1)
        {
            $upAry['b_car_id']          = $request->b_car_id;
            $ret = $this->createEngineeringCar($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            $upAry['car_kind']                  = 2;
            $upAry['car_no']                    = strtoupper($request->car_no);
            $upAry['car_type']                  = $request->car_type;
            $upAry['sdate']                     = $request->sdate;
            $upAry['last_car_inspection_date']  = $request->last_car_inspection_date;
            $upAry['img_path']                  = $filepath.$filename;
            $upAry['file1']                     = $file1;
            $upAry['file1N']                    = $file1N;
            $upAry['file2']                     = $file2;
            $upAry['file2N']                    = $file2N;
            $upAry['file3']                     = $file3;
            $upAry['file3N']                    = $file3N;
            //修改
            $ret = $this->createCar($upAry,$this->b_cust_id);
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
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

}
