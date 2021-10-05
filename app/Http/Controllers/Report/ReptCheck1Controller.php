<?php

namespace App\Http\Controllers\Report;

use Auth;
use Lang;
use Session;
use App\Lib\LogLib;
use App\Model\User;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use Illuminate\Http\Request;
use App\Model\Supply\b_supply;
use App\Http\Traits\SessTraits;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_factory;
use App\Model\View\view_wp_work;
use App\Model\View\view_used_rfid;
use App\Http\Controllers\Controller;
use App\Model\View\view_supply_user;
use App\Model\View\view_door_supply_member;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Model\View\view_door_supply_whitelist;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Model\View\view_door_supply_whitelist_pass;

class ReptCheck1Controller extends Controller
{
    use SessTraits,ReptDoorLogListTrait,SupplyMemberTrait;
    /*
    |--------------------------------------------------------------------------
    | ReptCheck1Controller
    |--------------------------------------------------------------------------
    |
    | [查詢]此人進廠判斷
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
        $this->hrefMain         = 'report_check1';
        $this->langText         = 'sys_rept';

        $this->pageTitleMain    = Lang::get($this->langText.'.title17');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list17');//列表

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
        $ip         = $request->ip();
        $menu       = $this->pageTitleMain;
        //參數
        $out = $js ='';
        $no        = $listAryCnt = 0;
        $supply_id = $b_cust_id  = $project_id = 0;
        $supply    = $name       = $project = $project_no = $cpc_tag = $rfid_code = $isUT = '';

        $uid       = trim($request->uid); //
        $supplyAry  = b_supply::getSelect();


        if(!$uid)
        {
            $uid = Session::get($this->hrefMain.'.search.uid','');
        } else {
            Session::put($this->hrefMain.'.search.uid',$uid);
        }

        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // POST
        //-------------------------------------------//
        if ($request->isMethod('post'))
        {
            if ($request->has('editY')) {
                $data = view_supply_user::isExist($uid);
                $id = $data->b_cust_id;
                $pid = $data->b_supply_id;

                //檢查目前進行中工程之承攬商成員
                $IsUsed = view_door_supply_member::isExist($pid, $id);
                //檢查[人員]配卡資格是否符合
                $used_rfid = view_used_rfid::isUserRFID($data->b_cust_id);
                //檢查[人員]配卡中的卡片狀態是否為停用
                $used_close_card = b_rfid_a::isUsedCloseCard($data->b_cust_id);
                if ($IsUsed) {
                    // 資格不符合，請洽詢管理員!
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10179'))
                        ->withInput();
                } elseif ($pid == $request->b_supply_id) {
                    // 承攬商已存在！
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_supply.supply_1014'))
                        ->withInput();
                } elseif ($used_rfid) {
                    // 配卡資格尚未失效，無法申請轉換承攬商!
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10180'))
                        ->withInput();
                } elseif ($used_close_card) {
                    // 配卡資格尚未失效，且卡片狀態已停用，無法申請轉換承攬商!
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10186'))
                        ->withInput();
                }elseif (empty($request->b_supply_id)) {
                    // 必填內容不完整，無法更新/新增
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10103'))
                        ->withInput();
                } 

                $upAry = [];
                $upAry['b_cust_id'] = $id;
                $upAry['b_supply_id'] = $request->b_supply_id;
                $ret = $this->setSupplyMember($id, $upAry, $this->b_cust_id);

                //2-1. 更新成功
                if ($ret) {
                    //沒有可更新之資料
                    if ($ret === -1) {
                        $msg = Lang::get('sys_base.base_10109');
                        return \Redirect::back()->withErrors($msg);
                    } else {
                        //動作紀錄
                        $action = 2;
                        LogLib::putLogAction($this->b_cust_id, $menu, $ip, $action, 'b_supply_member', $id);

                        //2-1-2 回報 更新成功
                        Session::flash('message', Lang::get('sys_base.base_10104'));
                        return \Redirect::to($this->hrefMain);
                    }
                } else {
                    $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg)) ? $ret->err->msg : Lang::get('sys_base.base_10105');
                    //2-2 更新失敗
                    return \Redirect::back()->withErrors($msg);
                }
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->text('uid',$uid,3,Lang::get($this->langText.'.rept_400'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'4','submit','','');
        $form->addRowCnt($html);

        $html = HtmlLib::Color(Lang::get($this->langText.'.rept_100009'),'red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($uid)
        {
            $no = 0;
            //table
            $table = new TableLib($hrefMain);
            //標題
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_401')]; //檢查階段
            $heads[] = ['title'=>Lang::get($this->langText.'.rept_403')]; //說明

            $table->addHead($heads,0);

            //1.尋找這個人是否存在
            $data = view_supply_user::isExist($uid);

            $td_head = '1.成員帳號資格';
            if(isset($data->b_cust_id))
            {
                $supply_id = $data->b_supply_id;
                $supply    = $data->supply;
                $b_cust_id = $data->b_cust_id;
                $name      = $data->name;
                $td_body = $b_cust_id.'，<b>'.$name.'</b>【'.HtmlLib::Color($supply,'blue').'】';
            } else {
                $td_body = HtmlLib::Color('查無此人，請確認搜尋資訊','red',1);
            }
            $tBody[] = [
                '1'=>[ 'name'=> $td_head],
                '2'=>[ 'name'=> $td_body],
            ];

            //2.工程案件資格
            if($b_cust_id)
            {
                $data = view_door_supply_member::where('b_cust_id',$b_cust_id)->first();
                $td_head = '2.工程案件資格';
                if(isset($data->b_cust_id))
                {
                    $cpc_tag    = $data->cpc_tag;
                    $project_id = $data->e_project_id;
                    $project    = $data->project;
                    $project_no = $data->project_no;
                    $td_body = '案號：'.$project_no.'，<b>'.$project.'</b>【'.HtmlLib::Color($cpc_tag,'blue').'】';
                } else {
                    if ($this->isRoot == 'Y') {
                        //目前預設為管理員才顯示轉公司的下拉
                        $html2 = $form->select('b_supply_id', $supplyAry, 0, 2, Lang::get($this->langText . '.rept_500'));
                        $html2 .= $form->submit(Lang::get('sys_btn.btn_38'), '5', 'editY') . '&nbsp;';
                        //輸出
                        $out .= $html2;
                    }
                    $td_body = HtmlLib::Color('工程案件資格失效，請確認工程案件進度與結束日期','red',1);
                }
                $tBody[] = [
                    '1'=>[ 'name'=> $td_head],
                    '2'=>[ 'name'=> $td_body],
                ];
            }

            //3.配卡資格
            if($project)
            {
                $data = view_door_supply_whitelist::where('b_cust_id',$b_cust_id)->first();
                $td_head = '3.配卡資格';
                if(isset($data->b_cust_id))
                {
                    $rfid_code  = $data->rfid_code;
                    $td_body = '配卡內碼：<b>'.$rfid_code.'</b>';
                } else {
                    $td_body = HtmlLib::Color('配卡資格失效，請確認是否配卡或配卡有效日','red',1);
                }
                $tBody[] = [
                    '1'=>[ 'name'=> $td_head],
                    '2'=>[ 'name'=> $td_body],
                ];
            }

            //4.教育訓練資格
            if($rfid_code)
            {
                $utAry = SHCSLib::getCode('UT_KIND');
                $data = view_door_supply_whitelist_pass::where('b_cust_id',$b_cust_id)->first();
                $td_head = '4.教育訓練資格';
                if(isset($data->b_cust_id))
                {
                    $isUT  = isset($utAry[$data->isUT])? $utAry[$data->isUT] : '';
                    $td_body = '有教育訓練合格紀錄，且尿檢資格：<b>'.$isUT.'</b>';
                } else {
                    $td_body = HtmlLib::Color('教育訓練資格失效，請確認教育訓練有效日','red',1);
                }
                $tBody[] = [
                    '1'=>[ 'name'=> $td_head],
                    '2'=>[ 'name'=> $td_body],
                ];
            }

            //5.工作許可證
            if($isUT)
            {
                list($workAmt,$data) = view_wp_work::getTodayReadyWork($project_id,$b_cust_id);
//                dd($workAmt,$data);
                $td_head = '5.工作許可證資格';
                if($workAmt)
                {
                    $td_body = '';
                    foreach ($data as $val)
                    {
                        $permit_no = isset($val['permit_no'])? $val['permit_no'] : '';
                        $door      = isset($val['door'])? $val['door'] : '';
                        $isWork    = isset($val['isWork'])? $val['isWork'] : '';
                        $in_door   = isset($va['in_door'])? '('.$val['in_door'].')' : '';
                        $td_body  .= '工單證號：<b>'.$permit_no.'</b>，允許進出門：<b>'.$door.'</b> ';
                        if($isWork == 'Y')
                        {
                            $td_body .= HtmlLib::Color('，正在執行該工單'.$in_door.'','blue',1);
                        }
                        $td_body .= '<br/>';
                    }

                } else {
                    $td_body = HtmlLib::Color('工作許可證資格失效，請確認是否有開立並審查合格之工單','red',1);
                }
                $tBody[] = [
                    '1'=>[ 'name'=> $td_head],
                    '2'=>[ 'name'=> $td_body],
                ];
            }

            //output
            $table->addBody($tBody);
            //輸出
            $out .= $table->output();
            unset($table);

            if($isUT && $b_cust_id)
            {
                $out .= '<br/>';
                $today = date('Y-m-d');
                list($listAryCnt,$listAry) = $this->getDoorDayRept([0,0,0,$today,'','',$b_cust_id]);
                //table
                $table = new TableLib($hrefMain);
                $heads = $tBody = [];
                //標題
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_203')]; //承攬商
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_230')]; //承攬商人員
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_301')]; //進出時間
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_11')]; //廠區
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_13')]; //門別
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_300')]; //進出
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_308')]; //進出結果
                $heads[] = ['title'=>Lang::get($this->langText.'.rept_311')]; //進廠判斷的工單

                $table->addHead($heads,0);
                if(count($listAry))
                {
                    $jogColorAry = ['工地負責人'=>'red','工負'=>'red','工安'=>'red','安衛人員'=>'red','特殊人員'=>'blue'];
                    foreach($listAry as $value)
                    {
                        $no++;
                        $id           = $value->id;
                        $name1        = $value->unit_name; //
                        $jobColor     = isset($jogColorAry[$value->job_kind])? $jogColorAry[$value->job_kind] : 'black';

                        $name2        = $value->b_cust_id.'<br/>'.$value->name.'（'.HtmlLib::Color($value->job_kind,$jobColor,1).'）'; //
                        $name3        = $value->door_stamp; //
                        $name4        = $value->store; //
                        $name5        = HtmlLib::Color($value->door_type_name,'black',1); //
                        $name6        = (($value->door_result) == 'N') ? HtmlLib::Color($value->door_result_name,'red',1) : $value->door_result_name; //

                        $name8        = $value->door; //

                        $name7        = ($value->permit_no)? HtmlLib::Color(Lang::get($this->langText.'.rept_312',['name1'=>$value->permit_no]),0,1) : '';
                        $name7       .= ($value->worker1)? '<br/>'.HtmlLib::Color(Lang::get($this->langText.'.rept_313',['name1'=>$value->worker1]),0,1) : '';
                        $name7       .= ($value->worker2)? '<br/>'.HtmlLib::Color(Lang::get($this->langText.'.rept_314',['name1'=>$value->worker2]),0,1) : '';

                        $tBody[] = [
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '8'=>[ 'name'=> $name8],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name7],
                        ];
                    }
                    $table->addBody($tBody);
                }
                //輸出
                $out .= $table->output();
                unset($table);
            }
        }

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTile,$out));
        $contents = $content->output();

        //jsavascript
        $js = '
        ';

        $css = '
            ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }

}
