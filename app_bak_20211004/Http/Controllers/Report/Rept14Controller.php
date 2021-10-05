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

class Rept14Controller extends Controller
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
        $this->hrefMain         = 'report_14';

        $this->pageTitleMain    = '承攬商進出異常排行板-每年/每月';//大標題
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
		$toyears   = date('Y');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory  = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);
		$rank      = array('0'=>'請選擇','10'=>'10','50'=>'50','100'=>'100');
		$datemenu  = array('0'=>'請選擇','1'=>'年度月份','2'=>'年度');
		
        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
		$years     = $request->years;
		$aid       = $request->aid; 
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
            $aid = Session::get($this->hrefMain.'.search.aid',10);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
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
				$query = " AND convert(varchar(7),A.door_date,120)= '".$months."'";
			}else if($hid='2')
			{
				$query = " AND DATEPART(YEAR,A.door_date)='".$years."'";
			}
				
            $SQL  = "SELECT TOP ".$aid."
					B.name AS 廠商名稱,COUNT(*) AS 筆數 FROM dbo.log_door_inout A
					JOIN dbo.b_supply B ON a.b_supply_id=B.id
					WHERE A.door_result='Y' 
					".$query."
					GROUP BY  B.name
					ORDER BY 筆數 DESC
					";
				

			
            $listAry = DB::select($SQL);
			//Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['承攬商名稱','筆數'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->廠商名稱;
                    $tmp[]  = $value->筆數;
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
		$html.= '<div id="monthss" style="display:block;">';
		$html.= $form->date('months',$months,2,'年度月份');
		$html.= '</div>';
		$html.= '<div id="yearss" style="display:none;">';
		$html.= $form->date('years',$years,2,'年度');
		$html.= '</div>';
		
		//$form->addRowCnt($html);
		
		$html.= $form->select('aid',$rank,$aid,2,'排名前');
		
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('','red',1);
        $form->addRow($html);
      
		
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'承攬商名稱'];
        $heads[] = ['title'=>'筆數'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->廠商名稱;
                $rept2           = $value->筆數;
                
				
                

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
							
                            
							
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
						$("#monthss")[0].style.display= "block";
						$("#yearss")[0].style.display= "none";
					}else if($("#hid").val()==2)
					{
						$("#monthss")[0].style.display= "none";
						$("#yearss")[0].style.display= "block";
					};
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
							$("#monthss")[0].style.display= "block";
							$("#yearss")[0].style.display= "none";
						}else  if($("#hid").val()==2)
						{
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
