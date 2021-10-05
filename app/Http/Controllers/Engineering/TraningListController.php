<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\TraningTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Storage;

class TraningListController extends Controller
{
    use TraningTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TraningListController
    |--------------------------------------------------------------------------
    |
    | 教育訓練開課 維護
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'etraninglist';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'new_etraningmemberlist';
        $this->hrefMainDetail2  = 'etraningtimelist';
        $this->hrefMainDetail3  = 'etraningmember';
        $this->hrefMainNew      = 'new_etraninglist';
        $this->routerPost       = 'postETraningList';

        $this->pageTitleMain    = Lang::get($this->langText.'.title28');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list28');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new28');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit28');//編輯

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
        $out = $js ='';
        $no  = 0;
        $listAry    = [];
        $closeAry   = SHCSLib::getCode('CLOSE',1);
        $courseAry  = et_course::getSelect();
        $cid    = $request->cid;
        $tid    = $request->tid;
        $sdate  = $request->sdate;
        $edate  = $request->edate;
        $close  = $request->close;
        if($request->has('clear'))
        {
            $cid = $tid = $sdate = $edate = $close = '';
            Session::forget($this->langText.'.search');
        }
        if(!$cid)
        {
            $cid = Session::get($this->langText.'.search.cid',1);
        } else {
            Session::put($this->langText.'.search.cid',$cid);
        }
        if(!$tid)
        {
            $tid = Session::get($this->langText.'.search.tid','');
        } else {
            Session::put($this->langText.'.search.tid',$tid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->langText.'.search.sdate','');
        } else {
            Session::put($this->langText.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->langText.'.search.edate','');
        } else {
            Session::put($this->langText.'.search.edate',$edate);
        }
        if(!$close)
        {
            $close = Session::get($this->langText.'.search.close','N');
        } else {
            Session::put($this->langText.'.search.close',$close);
        }
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
        if($cid)
        {
            $listAry = $this->getApiTraningList($cid,$tid,$sdate,$edate,$close);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        //$form->addHr();
        $html = $form->select('cid',$courseAry,$cid,2,Lang::get($this->langText.'.engineering_42'));
        $html.= $form->text('tid',$tid,2,Lang::get($this->langText.'.engineering_43'));
        $html.= $form->select('close',$closeAry,$close,2,Lang::get($this->langText.'.engineering_33'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_44'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1028'),'red',1);
        $form->addRow($html,4,1);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_39')]; //開課代碼
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //課程
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_43')]; //授課教師
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_44')]; //有效日期
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_9')];  //結束日期
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_45')]; //有效天數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_46')]; //上課時段
//        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_87')]; //上課成員

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->course; //
                $name2        = $value->teacher; //
                $name3        = $value->sdate; //
                $name4        = $value->edate; //
                $name5        = $value->valid_day; //
                $name6        = $value->course_no; //

                //按鈕
                $LicenseBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_36'),3); //按鈕
                //$MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕

                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_41'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '6'=>[ 'name'=> $name6],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '21'=>[ 'name'=> $LicenseBtn],
                            '99'=>[ 'name'=> $btn],
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        language: "zh-TW"
                    });
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
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $courseAry    = et_course::getSelect();
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
        } else {
            //資料明細
            $A1         = $getData->et_course_id; //
            $A2         = $getData->teacher; //
            $A3         = $getData->sdate; //
            $A4         = $getData->edate; //
            $A5         = $getData->valid_day; //
            $A6         = $getData->course_no; //
            $A13        = $getData->memo; //

            $file1      = strlen($getData->tran_file1)? SHCSLib::url('file/','A'.$id,'sid=Course') : '';

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $form->select('course_id',$courseAry,$A1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //課程
        $html = $form->text('course_no',$A6);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_39'),1);
        //老師
        $html = $form->text('teacher',$A2);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_43'),1);
        //開始日期
        $html = $form->date('sdate',$A3);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_44'),1);
        //結束日期
        $html = $form->date('edate',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //有效天數
        $html = $form->text('valid_day',$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_45'),1);
        //上課用檔案
        $html = $form->file('file1');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_47'));
        //上課用檔案
        $html = ($file1)? $form->linkbtn($file1, Lang::get('sys_btn.btn_29'),1) : '';
        $html.= $form->file('file2');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_48'));
        //上課用檔案
        $html = $form->file('file3');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_49'));
        //說明
        $html = $form->textarea('memo',$A13);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));
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
        if( !$request->has('agreeY') || !$request->id || !$request->course_id || !$request->course_no || !$request->teacher || !$request->valid_day || !$request->sdate || !$request->edate)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //同一個課程不得重複開立
        elseif(et_traning::isExist($request->course_id,$request->sdate,$request->edate,SHCSLib::decode($request->id))){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1006'))
                ->withInput();
        }
        //開課代碼
        elseif(et_traning::isCourseNoExist($request->course_no,SHCSLib::decode($request->id))){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1007'))
                ->withInput();
        }
        //開始日期不可大於結束日期
        elseif(strtotime($request->sdate) > strtotime($request->edate)){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1002'))
                ->withInput();
        }
        //不可小於今日
        elseif(strtotime($request->edate) < strtotime(date('Y-m-d'))){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1003'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $file1= $file2 = $file3 = '';
            //課程檔案
            if($request->hasFile('file1'))
            {
                $File       = $request->file1;
                $extension  = $File->extension();
                $filesize   = $File->getSize();
                //[錯誤]格式錯誤
                if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                    return \Redirect::back()
                        ->withErrors($extension.Lang::get('sys_base.base_10120'))
                        ->withInput();
                } elseif($filesize > $this->fileSizeLimit1) {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                        ->withInput();
                } else {
                    //圖片位置
                    $filepath = config('mycfg.course_path').date('Y/').$id.'/';
                    $filename = $id.'_A.'.$extension;
                    $file1    = $filepath.$filename;
                    Storage::put($file1,file_get_contents($File));
                }
            }
            if($request->hasFile('file2'))
            {
                $File       = $request->file2;
                $extension  = $File->extension();
                $filesize   = $File->getSize();
                //[錯誤]格式錯誤
                if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                    return \Redirect::back()
                        ->withErrors($extension.Lang::get('sys_base.base_10120'))
                        ->withInput();
                } elseif($filesize > $this->fileSizeLimit1) {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                        ->withInput();
                } else {
                    //圖片位置
                    $filepath = config('mycfg.course_path').date('Y/').$id.'/';
                    $filename = $id.'_B.'.$extension;
                    $file2    = $filepath.$filename;
                    Storage::put($file2,file_get_contents($File));
                }
            }
            if($request->hasFile('file3'))
            {
                $File       = $request->file3;
                $extension  = $File->extension();
                $filesize   = $File->getSize();
                //[錯誤]格式錯誤
                if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                    return \Redirect::back()
                        ->withErrors($extension.Lang::get('sys_base.base_10120'))
                        ->withInput();
                } elseif($filesize > $this->fileSizeLimit1) {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                        ->withInput();
                } else {
                    //圖片位置
                    $filepath = config('mycfg.course_path').date('Y/').$id.'/';
                    $filename = $id.'_C.'.$extension;
                    $file3    = $filepath.$filename;
                    Storage::put($file3,file_get_contents($File));
                }
            }
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['et_course_id']       = is_numeric($request->course_id) ? $request->course_id : 0;
        $upAry['course_no']          = strlen($request->course_no) ?     $request->course_no : '';
        $upAry['valid_day']          = is_numeric($request->valid_day) ? $request->valid_day : 0;
        $upAry['teacher']            = $request->teacher;
        $upAry['sdate']              = $request->sdate;
        $upAry['edate']              = $request->edate;
        $upAry['tran_file1']         = $file1;
        $upAry['tran_file2']         = $file2;
        $upAry['tran_file3']         = $file3;
        $upAry['memo']               = $request->memo;
        $upAry['isClose']            = ($request->isClose == 'Y')? 'Y' : 'N';
        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createTraning($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setTraning($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_traning',$id);

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
        //參數
        $js = $contents = '';
        $courseAry  = et_course::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //課程
        $html = $form->select('course_id',$courseAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //開課代碼
        $html = $form->text('course_no');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_39'),1);
        //老師
        $html = $form->text('teacher');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_43'),1);
        //開始日期
        $html = $form->date('sdate');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_44'),1);
        //結束日期
        $html = $form->date('edate');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //有效天數
        $html = $form->text('valid_day');
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_45'),1);
        //上課用檔案
        $html = $form->file('file1');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_47'));
        //上課用檔案
        $html = $form->file('file2');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_48'));
        //上課用檔案
        $html = $form->file('file3');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_49'));
        //說明
        $html = $form->textarea('memo');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

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
           $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            
            $("#stime,#etime").timepicker({
                showMeridian: false
            })
            
            $( "#charge_dept" ).change(function() {
                        var eid = $("#charge_dept").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",  
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#charge_user option").remove();
                             $.each(result, function(key, val) {
                                $("#charge_user").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
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
