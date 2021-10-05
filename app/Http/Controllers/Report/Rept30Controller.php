<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Factory\b_factory_d;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\e_project;
use App\Model\User;
use App\Model\WorkPermit\wp_work_list;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DateTime;
use DB;
use Excel;

class Rept30Controller extends Controller
{
    use SessTraits, WorkPermitWorkOrderTrait;
    /*
    |--------------------------------------------------------------------------
    | ReptExampleController
    |--------------------------------------------------------------------------
    |
    | [報表]報表名稱
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
        $this->hrefMain         = 'report_30';
        $this->hrefMainDetail   = 'wpworkorder/';
        $this->hrefProcess      = 'workpermitprocessshow';

        $this->pageTitleMain    = '工程案件開立許可證報表';//大標題
        $this->pageTitleList    = '';//列表


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
        $out = $js  ='';
        $no         = 0;
        $today      = date('Y-m-d');
        $tomonths  = date('Y-m');
        $supplyAry  = b_supply::getSelect();       //承攬商陣列
        $storeAry   = b_factory::getSelect();      //廠區陣列
		$bfactory   = b_factory_d::getSelect();     //門別陣列
		$typeAry    = e_project_type::getSelect();  //案件分類陣列
		$project_id = e_project::getSelect();     //工程案件
        $aproc      = SHCSLib::getCode('PERMIT_APROC',0);

        $bc_id     = SHCSLib::decode($request->id); //身分證
        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $months    = $request->months;
        $aid       = $request->aid; //工程案件
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //承商名稱
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $bc_id = $sdate = $edate = $months = $aid = $bid = $cid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$months)
        {
            $months = Session::get($this->hrefMain.'.search.months',$tomonths);
        } else {
            Session::put($this->hrefMain.'.search.months',$months);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$bc_id)
        {
            $bc_id = Session::get($this->hrefMain.'.search.bc_id','');
        } else {
            Session::put($this->hrefMain.'.search.bc_id',$bc_id);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        $listAry = array();
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        // if(1)
       if((!empty($aid) || !empty($bc_id) || !empty($bid)) && !empty($months)) //至少一個搜尋條件(案件名稱or承攬商)
        {
            $mdt = new DateTime($months);
            $listAry = $this->getApiWorkPermitWorkOrderList(0,"",[$bid,$aid,'','',0],[[5,6],0,0],[0,0,0,0,0,0],[$mdt->format('Y-m-d'), $mdt->format('Y-m-t'), ""],['N',0],'N',$bc_id);
            Session::put('wpworkorder.Record',$listAry);
            
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案件','許可證編號','承攬商','廠區','監造部門','監造負責人','轄區部門','危險等級','進度'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project;
                    $tmp[]  = $value->permit_no;
					$tmp[]  = $value->supply;
                    $tmp[]  = $value->store;
                    $tmp[]  = $value->be_dept_id2_name;
                    $tmp[]  = $value->charge_user_name;
                    $tmp[]  = $value->be_dept_id1_name;
                    $tmp[]  = $value->wp_permit_danger;
                    $tmp[]  = $value->list_aproc;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '工程案件報表_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('months',$months,2,'年度月份');
        $html.= $form->select('aid',$project_id,$aid,3,'案件名稱');
		$html.= $form->select('bid',$supplyAry,$bid,3,'承攬商');
		$form->addRowCnt($html);
		$html = '';
        if ($bc_id) {
            $html = $form->text('bc_id',  SHCSLib::genBCID($bc_id), 2, '身分證', '', 1);
        }
        $form->addRowCnt($html);
        $html = '<div style="display: flex; justify-content: space-between; align-items: center; text-align:right; margin-left: 15px; margin-right: 15px; margin-top: 15px;">';
        $html .= '<div>'.HtmlLib::Color(Lang::get('sys_rept.rept_100011'),'red',1).'</div>';
        $html .= '<div>';
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $html .= '</div>';
        $html .= '</div>';
        $form->addRowCnt($html);

        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請搜尋條件(廠區＆承商)','red',1);
        
      
		$form->addHr();
        //輸出
        $out .= $form->output(1);
		
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'工程案件'];
        $heads[] = ['title'=>'許可證編號'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'廠區'];
		$heads[] = ['title'=>'監造部門'];
		$heads[] = ['title'=>'監造負責人'];
        $heads[] = ['title'=>'轄區部門'];
		$heads[] = ['title'=>'危險等級'];
		$heads[] = ['title'=>'進度'];
		$heads[] = ['title'=>'歷程'];
		$heads[] = ['title'=>'維護'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project;
                $rept2           = $value->permit_no;
                $rept3           = $value->supply;
                $rept4           = $value->store;
                $rept5           = $value->be_dept_id2_name;
                $rept6           = $value->charge_user_name;
                $rept7           = $value->be_dept_id1_name;
                $rept8           = $value->wp_permit_danger;
                $rept9           = $value->list_aproc;
                
                $detail_url   = ($bc_id)? ('bc_id='.SHCSLib::encode($bc_id)) : '';
                $list_id      = wp_work_list::isExist($value->id,1);
                $list_url     = ($list_id)? ('wid='.SHCSLib::encode($value->id).'&lid='.SHCSLib::encode($list_id)) : '';
                //按鈕
                $btn             = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail, $value->id, $detail_url), Lang::get('sys_btn.btn_13'), 1); //按鈕
                if ($value->aproc == 'A') {
                    $btn2        = ''; //按鈕
                } else {
                    $btn2        = HtmlLib::btn(SHCSLib::url($this->hrefProcess, '', $list_url), Lang::get('sys_btn.btn_30'), 4); //按鈕
                }

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
							'3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
							'5'=>[ 'name'=> $rept5],
							'6'=>[ 'name'=> $rept6],
							'7'=>[ 'name'=> $rept7],
							'8'=>[ 'name'=> $rept8],
                            '9'=>[ 'name'=> $rept9],
                            '10'=>[ 'name'=> $btn2],
                            '11'=>[ 'name'=> $btn],
							
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
        $content->rowTo($content->box_table($tbTile,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
					$("#months").datepicker({
						format: "yyyy-mm",
						language: "zh-TW",
						viewMode: "months",
						minViewMode: "months",
					});
             });';

        $css = '
                
             
            ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }

}
