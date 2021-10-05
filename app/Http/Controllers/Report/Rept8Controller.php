<?php

namespace App\Http\Controllers\Report;

use App\Exports\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Factory\b_factory_d;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept8Controller extends Controller
{
    use SessTraits;
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
        $this->hrefMain         = 'report_8';

        $this->pageTitleMain    = '廠區人員進出統計';//大標題
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
        $out = $js ='';
        $no        = 0;
        $today     = date('Y-m-d');
		$tomonths  = date('Y-m');
		$toyears  = date('Y');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);
        $datemenu  =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
		
        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
		$years    = $request->years;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
        $hid       = $request->hid; //日期選單
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $hid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
		if(!$months)
        {
            $months = Session::get($this->hrefMain.'.search.months',$tomonths);
        } else {
            //if(strtotime($months) > strtotime($tomonths)) $months = $tomonths;
			//date('Y-m-d', strtotime('+1 year'));
            Session::put($this->hrefMain.'.search.months',$months);
        }
		if(!$years)
        {
            $years = Session::get($this->hrefMain.'.search.years',$toyears);
        } else {
            Session::put($this->hrefMain.'.search.years',$years);
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
            $cid = Session::get($this->hrefMain.'.search.cid',0);
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
		if(!$hid)
        {
            $hid = Session::get($this->hrefMain.'.search.hid',1);
        } else {
            Session::put($this->hrefMain.'.search.hid',$hid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(1)
//        if(!$aid || !$bid)
        {
			if($hid=='1')
			{
				$query = " AND A.door_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$query = " AND convert(varchar(7),A.door_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$query = " AND DATEPART(YEAR,A.door_date)='".$years."'";
			}
            $SQL = "SELECT DISTINCT B.name AS b_factory,C.name AS b_factory_d,count(X.Number) AS Number FROM (
							SELECT A.name,A.b_factory_id,A.b_factory_d_id,COUNT(DISTINCT A.name) Number
							FROM dbo.log_door_inout A 
							WHERE err_code=0".$query."
							
							GROUP BY A.name,A.b_factory_id,A.b_factory_d_id
							) X
							JOIN dbo.b_factory B ON X.b_factory_id=B.id
							JOIN dbo.b_factory_d C ON X.b_factory_d_id=C.id
							WHERE :factory_id IN (B.id,0) AND :factory_d_id IN (C.id,0)
							GROUP BY B.name, C.name";
					
					
								
            $listAry = DB::select($SQL,['factory_id'=>$aid,'factory_d_id'=>$cid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['廠區','門別','進場人數與出廠人數'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->b_factory;
                    $tmp[]  = $value->b_factory_d;
                    $tmp[]  = $value->Number;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '範例_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->select('hid',$datemenu,$hid,2,'日期選單');
		$html .= '<div id="dates" style="display:block;">';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
		$html.= $form->date('edate',$edate,2,'結束日期');
		$html .= '</div>';
		$html .= '<div id="monthss" style="display:none;">';
		$html.= $form->date('months',$months,2,'年度月份');
		$html .= '</div>';
		$html .= '<div id="yearss" style="display:none;">';
		$html.= $form->date('years',$years,2,'年度');
		$html .= '</div>';
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
		$html.= $form->select('cid',$bfactory,$cid,2,'門別');



        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);    
		
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'廠區'];
        $heads[] = ['title'=>'門別']; 
		$heads[] = ['title'=>'進場人數與出廠人數']; 

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->b_factory;
                $rept2           = $value->b_factory_d;
                $rept3           = $value->Number;
                

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
                            		
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
					if($("#hid").val()==1)
					{
						$("#dates")[0].style.display= "block";
						$("#monthss")[0].style.display= "none";
						$("#yearss")[0].style.display= "none";
					}
					else if($("#hid").val()==2)
					{
						$("#dates")[0].style.display= "none";
						$("#monthss")[0].style.display= "block";
						$("#yearss")[0].style.display= "none";
					}else if($("#hid").val()==3)
					{
						$("#dates")[0].style.display= "none";
						$("#monthss")[0].style.display= "none";
						$("#yearss")[0].style.display= "block";
					};
					$("#sdate,#edate").datepicker({
						format: "yyyy-mm-dd",
						language: "zh-TW",
					});
					$("#months").datepicker({
						format: "yyyy-mm",
						language: "zh-TW",
						viewMode: "months",
						minViewMode: "months",
					});
					$("#years").datepicker({
						format: " yyyy", 
						viewMode: "years", 
						minViewMode: "years",
						language: "zh-TW"
					});
					$("#hid").change(function(){
						
						if($("#hid").val()==1)
						{
							$("#dates")[0].style.display= "block";
							$("#monthss")[0].style.display= "none";
							$("#yearss")[0].style.display= "none";
						}
						else if($("#hid").val()==2)
						{
							$("#dates")[0].style.display= "none";
							$("#monthss")[0].style.display= "block";
							$("#yearss")[0].style.display= "none";
						}else  if($("#hid").val()==3)
						{
							$("#dates")[0].style.display= "none";
							$("#monthss")[0].style.display= "none";
							$("#yearss")[0].style.display= "block";
						}
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
