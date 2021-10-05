<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\CourseTrait;
use App\Http\Traits\Engineering\CourseTypeTrait;
use App\Http\Traits\Engineering\LicenseTypeTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\et_course_type;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class CourseController extends Controller
{
    use CourseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | CourseController
    |--------------------------------------------------------------------------
    |
    | 課程 維護
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'ecourse';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'ecourse/';
        $this->hrefMainNew      = 'new_ecourse';
        $this->routerPost       = 'postECourse';

        $this->pageTitleMain    = Lang::get($this->langText.'.title11');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list11');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new11');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit11');//編輯

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
        //參數
        $out = $js ='';
        $no  = 0;
        $passAry = SHCSLib::getCode('PASS');
        $closeAry = SHCSLib::getCode('CLOSE');
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiCourseList();
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //名稱
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_3')]; //分類
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_45')]; //有效天數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_33')]; //狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_47')]; //上課檔案1
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_48')]; //上課檔案2
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_49')]; //上課檔案3

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->name; //
                $name2        = $value->course_type_name; //
                $name3        = $value->valid_day; //
                $fileLink1    = ($value->filePath1)? $form->linkbtn($value->filePath1, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                $fileLink2    = ($value->filePath2)? $form->linkbtn($value->filePath2, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                $fileLink3    = ($value->filePath3)? $form->linkbtn($value->filePath3, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) :''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '11'=>[ 'name'=> $fileLink1],
                            '12'=>[ 'name'=> $fileLink2],
                            '13'=>[ 'name'=> $fileLink3],
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $typeAry = et_course_type::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->course_type; //
            $A3         = $getData->memo; //
            $A4         = $getData->valid_day; //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A11        = ($getData->isDoorRule == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = $form->text('name',$A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_42'),1);
        //分類
        $html = $form->select('course_type',$typeAry,$A2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);
        //有效天數
        $html = $form->number('valid_day',$A4,2,1,9999);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_45'),1);
        //上課用檔案1
        $html  = ($getData->filePath1)? $form->linkbtn($getData->filePath1, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file1');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_47'));
        //上課用檔案2
        $html  = ($getData->filePath2)? $form->linkbtn($getData->filePath2, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file2');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_48'));
        //上課用檔案3
        $html  = ($getData->filePath3)? $form->linkbtn($getData->filePath3, Lang::get('sys_btn.btn_29'),4,'','','','_blank') : '';
        $html .= $form->file('file3');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_49'));
        //說明
        $html = $form->textarea('memo',$A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_13'));
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
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
        if( !$request->has('agreeY') || !$request->id || !$request->name || !$request->course_type )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }if(!$request->valid_day)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1056'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $file1= $file1N = $file2 = $file2N = $file3 = $file3N = '';

        //檔案1
        if($request->hasFile('file1'))
        {
            $File       = $request->file1;
            $extension  = $File->extension();
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10120'))
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
                    ->withErrors(Lang::get('sys_base.base_10120'))
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
                    ->withErrors(Lang::get('sys_base.base_10120'))
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
        $upAry['name']              = $request->name;
        $upAry['course_code']       = isset($request->course_code)? $request->course_code : '';
        $upAry['course_type']       = is_numeric($request->course_type) ? $request->course_type : 0;
        $upAry['valid_day']         = $request->valid_day;
        $upAry['memo']              = $request->memo;
        $upAry['file1']             = $file1;
        $upAry['file1N']            = $file1N;
        $upAry['file2']             = $file2;
        $upAry['file2N']            = $file2N;
        $upAry['file3']             = $file3;
        $upAry['file3N']            = $file3N;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createCourse($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setCourse($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_course',$id);

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
        $typeAry = et_course_type::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_31'),1);
        //代碼
        $html = $form->text('course_code');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_54'),1);
        //分類
        $html = $form->select('course_type',$typeAry,1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_3'),1);
        //有效天數
        $html = $form->number('valid_day',365,2,1,9999);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_45'),1);
        //上課用檔案1
        $html  = $form->file('file1');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_47'));
        //上課用檔案2
        $html  = $form->file('file2');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_48'));
        //上課用檔案3
        $html  = $form->file('file3');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_49'));
        //說明
        $html = $form->textarea('memo','');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_13'));


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
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
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
           
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
