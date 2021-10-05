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
use App\Model\Engineering\e_violation;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept16Controller extends Controller
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
        $this->hrefMain         = 'report_16';
		$this->hrefMain1         = 'report_17';

        $this->pageTitleMain    = '違規統計';//大標題
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
        $out = $js   ='';
        $no          = 0;
        $monthfirstDay = date('Y-m-01');
        $today       = date('Y-m-d');
		$tomonths  = date('Y-m');
		$toyears   = date('Y');
        $supplyAry   = b_supply::getSelect();  //承攬商陣列
        $storeAry    = b_factory::getSelect(); //廠區陣列
		$bfactory    = b_factory_d::getSelect();//門別陣列
		$eviolation  = e_violation::getSelect(); //違規事項
        $aproc       = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);
		$datemenu    =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
		
        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
		$years     = $request->years;
        $aid       = $request->aid; //案號
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //人員
		$did       = $request->did; //案名
		$eid       = $request->eid; //違規事項
        $fid       = $request->fid; //承商名稱
		$hid       = $request->hid; //日期選單
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $fid = $hid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$monthfirstDay);
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
            $cid = Session::get($this->hrefMain.'.search.cid','');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did','');
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
        }
        if(!$eid)
        {
            $eid = Session::get($this->hrefMain.'.search.eid','');
        } else {
            Session::put($this->hrefMain.'.search.eid',$eid);
        }
        if(!$fid)
        {
            $fid = Session::get($this->hrefMain.'.search.fid','');
        } else {
            Session::put($this->hrefMain.'.search.fid',$fid);
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
            $query = "";
            if (!empty($fid)) { // 承攬商名稱
                $query .= " AND C.name LIKE '%$fid%' "; 
            }
			if($hid=='1')
			{
				$query .= " AND A.apply_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$query .= " AND convert(varchar(7),A.apply_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$query .= " AND DATEPART(YEAR,A.apply_date)='".$years."'";
			}
            $SQL = "SELECT B.project_no AS 工程案號,B.name AS 工程名稱,COUNT(*) 數量 FROM e_violation_contractor A
					JOIN dbo.e_project B ON A.e_project_id=B.id
					JOIN dbo.b_supply C ON A.b_supply_id=C.id
					WHERE :project_no IN (B.project_no,'') AND :projectname IN (B.name,'') 
					AND :username IN (B.name,'') AND :e_violation_id IN (A.e_violation_id,'') and :supply_id IN (C.id,'')
					".$query."
					GROUP BY B.project_no,B.name
					";
			//$listAry = DB::select($SQL);
            $listAry = DB::select($SQL,['project_no'=>$aid,'supply_id'=>$bid,'projectname'=>$did,'username'=>$cid,'e_violation_id'=>$eid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','工程名稱','數量'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->工程案號;
                    $tmp[]  = $value->工程名稱;
					$tmp[]  = $value->數量;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '違規統計_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->select('hid',$datemenu,$hid,2,'日期選單');
		$html.= '<div id="dates" style="display:block;">';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
		$html.= $form->date('edate',$edate,2,'結束日期');
		$html.= '</div>';
		$html.= '<div id="monthss" style="display:none;">';
		$html.= $form->date('months',$months,2,'年度月份');
		$html.= '</div>';
		$html.= '<div id="yearss" style="display:none;">';
		$html.= $form->date('years',$years,2,'年度');
		$html.= '</div>';
        $html.= $form->select('eid',$eviolation,$eid,2,'違規事項');
        $form->addRowCnt($html);

		$html = $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('fid',$fid,2,'承攬商名稱');
        $html.= $form->text('aid',$aid,2,'工程案號'); 
		$html.= $form->text('did',$did,2,'工程案名');
        $form->addRowCnt($html);
        
		$html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
		
		$html.= '</div>';
        $form->addRowCnt($html);
        //至少一個搜尋條件
        //$html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        //$form->addRow($html);
      
		
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'工程案號'];
		$heads[] = ['title'=>'工程名稱'];
        $heads[] = ['title'=>'數量'];
		$heads[] = ['title'=>'功能'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->工程案號;
                $rept2           = $value->工程名稱;
				$rept3           = $value->數量;

                // if($hid=='1')
                // {
                // 	$Btn         = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','aid='.SHCSLib::encode($aid).'&bid='.SHCSLib::encode($bid).
                // 					'&cid='.SHCSLib::encode($cid).'&did='.SHCSLib::encode($did).'&eid='.SHCSLib::encode($eid).
                // 					'&sdate='.SHCSLib::encode($sdate).'&edate='.SHCSLib::encode($edate).'&hid='.SHCSLib::encode($hid))
                // 					,'明細',2); //按鈕
                // }
                // else if($hid=='2')
                // {
                // 	$Btn         = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','aid='.SHCSLib::encode($aid).'&bid='.SHCSLib::encode($bid).
                // 					'&cid='.SHCSLib::encode($cid).'&did='.SHCSLib::encode($did).'&eid='.SHCSLib::encode($eid).
                // 					'&months='.SHCSLib::encode($months).'&hid='.SHCSLib::encode($hid))
                // 					,'明細',2); //按鈕

                // }else if($hid='3')
                // {
                // 	$Btn         = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','aid='.SHCSLib::encode($aid).'&bid='.SHCSLib::encode($bid).
                // 					'&cid='.SHCSLib::encode($cid).'&did='.SHCSLib::encode($did).'&eid='.SHCSLib::encode($eid).
                // 					'&years='.SHCSLib::encode($years).'&hid='.SHCSLib::encode($hid))
                // 					,'明細',2); //按鈕
                // }

                $params = json_encode(array('project_no'=>$rept1,'project_name'=>$did,'supply_id'=>$bid,'supply_name'=>$fid,'violation_id'=>$eid,'hid'=>$hid,'sdate'=>$sdate,'edate'=>$edate,'months'=>$months,'years'=>$years));
                $Btn = HtmlLib::btn(
                    SHCSLib::url($this->hrefMain1, '', 'encode=' . SHCSLib::encode($params)),
                    '明細',
                    2
                ); //按鈕
				
                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
							'3'=>[ 'name'=> $rept3],
                            '4'=>[ 'name'=> $Btn],
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
