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

class Rept17Controller extends Controller
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
        $this->hrefMain         = 'report_17';
        $this->hrefMain1         = 'report_16';

        $this->pageTitleMain    = '違規清單(明細)';//大標題
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
        $monthfirstDay = date('Y-m-01');
        $today     = date('Y-m-d');
        $tomonths  = date('Y-m');
		$toyears   = date('Y');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
		$eviolation = e_violation::getSelect(); //違規事項陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);
        $datemenu    =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');

        if (isset($request->encode)) {
            $params = json_decode(SHCSLib::decode($request->encode), true);
        }

        $sdate     = isset($params['sdate']) ? $params['sdate'] : $request->sdate;
        $edate     = isset($params['edate']) ? $params['edate'] : $request->edate;
        $months    = isset($params['months']) ? $params['months'] : $request->months;
        $years     = isset($params['years']) ? $params['years'] : $request->years;
        $aid       = $request->aid; //廠區
        $bid       = isset($params['supply_id']) ? $params['supply_id'] : $request->bid; //承商
        $cid       = isset($params['project_no']) ? $params['project_no'] : $request->cid; //案號
        $did       = isset($params['project_name']) ? $params['project_name'] : $request->did; //案名
        $eid       = $request->eid; //人員
        $fid       = isset($params['violation_id']) ? $params['violation_id'] : $request->fid; //違規事項
        $gid       = isset($params['supply_name']) ? $params['supply_name'] : $request->gid; //承商名稱
        $hid       = isset($params['hid']) ? $params['hid'] : $request->hid; //日期選單
        $iid       = isset($params['iid']) ? $params['iid'] : $request->iid; //身分證

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $fid = $gid = $iid = '';
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
        if(!$gid)
        {
            $gid = Session::get($this->hrefMain.'.search.gid','');
        } else {
            Session::put($this->hrefMain.'.search.gid',$gid);
        }
        if(!$hid)
        {
            $hid = Session::get($this->hrefMain.'.search.hid',1);
        } else {
            Session::put($this->hrefMain.'.search.hid',$hid);
        }
        if(!$iid)
        {
            $iid = Session::get($this->hrefMain.'.search.iid','');
        } else {
            Session::put($this->hrefMain.'.search.iid',$iid);
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

            // 模糊查詢條件
            $query = "";
            if (!empty($gid)) { // 承攬商名稱
                $query .= " AND C.name LIKE '%$gid%' ";
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
            $SQL = "SELECT B.project_no AS 工程案號,B.name AS 工程名稱,C.name AS 廠商名稱,D.name AS 承攬商人員,D.account AS 身分證,
					A.apply_stamp AS 違規時間, A.violation_record4 AS 違規分類,A.violation_record2 AS 違規法規,
					A.violation_record1 AS 違規事項, A.violation_record3 AS 違規罰則,ISNULL(CONVERT(VARCHAR(10),A.limit_edate,120),'無') 再次入場日期
					FROM e_violation_contractor A
					JOIN dbo.e_project B ON A.e_project_id=B.id
					JOIN dbo.b_supply C ON A.b_supply_id=C.id
					JOIN dbo.b_cust D ON A.b_cust_id=D.id
					WHERE :project_no IN (B.project_no,'') AND :projectname IN (B.name,'') AND :username IN (D.name,'') AND :e_violation_id IN (A.e_violation_id,'')
					AND :supply_id IN (C.id,'') AND :iid IN (D.account, '')
                    $query
					--AND DATEPART(MONTH,A.apply_date)=11 AND DATEPART(YEAR,A.apply_date)=DATEPART(YEAR,GETDATE())   --(月份)
					--AND DATEPART(YEAR,A.apply_date)=2020     --(年度)
					";
					
			
			
            //$listAry = DB::select($SQL);
            $listAry = DB::select($SQL,['project_no'=>$cid,'supply_id'=>$bid,'projectname'=>$did,'username'=>$eid,'e_violation_id'=>$fid, 'iid'=>$iid]);
			//Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','工程名稱','承攬商','承攬商成員','身分證','違規時間','違規分類','違規法規','違規事項','違規法則','再次入場日期'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->工程案號;
                    $tmp[]  = $value->工程名稱;
					$tmp[]  = $value->廠商名稱;
					$tmp[]  = $value->承攬商人員;
					$tmp[]  = substr($value->身分證,0,3) . '*****' . substr($value->身分證,-2);
					$tmp[]  = $value->違規時間;
					$tmp[]  = $value->違規分類;
					$tmp[]  = $value->違規法規;
					$tmp[]  = $value->違規事項;
					$tmp[]  = $value->違規罰則;
					$tmp[]  = $value->再次入場日期;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '違規清單_'.date('Ymdhis').'.xlsx');
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
        $form->addRowCnt($html);

		$html = $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('gid',$gid,2,'承攬商名稱'); 
        $html.= $form->text('eid',$eid,2,'姓名');
        $html.= $form->text('iid',$iid,2,'身分證');
        $form->addRowCnt($html);

		$html = $form->select('fid',$eviolation,$fid,2,'違規事項');
		$html.= $form->text('did',$did,2,'案名'); 
		$html.= $form->text('cid',$cid,2,'案號'); 
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        if (isset($params)) {
            $html .= $form->linkbtn($this->hrefMain1, '返回', 2);
        }
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $html.= '</div>';
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
        $heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'工程案名'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'承攬商成員'];
		$heads[] = ['title'=>'身分證'];
		$heads[] = ['title'=>'違規時間'];
		$heads[] = ['title'=>'違規分類'];
		$heads[] = ['title'=>'違規法規'];
		$heads[] = ['title'=>'違規事項'];
		$heads[] = ['title'=>'違規罰則'];
		$heads[] = ['title'=>'再次入場日期'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->工程案號;
                $rept2           = $value->工程名稱;
				$rept3           = $value->廠商名稱;
				$rept4           = $value->承攬商人員;
				$rept5           = substr($value->身分證,0,3) . '*****' . substr($value->身分證,-2);
				$rept6           = $value->違規時間;
				$rept7           = $value->違規分類;
				$rept8           = $value->違規法規;
				$rept9           = $value->違規事項;
				$rept10           = $value->違規罰則;
				$rept11           = $value->再次入場日期;
                
				
                

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
							'10'=>[ 'name'=> $rept10],
							'11'=>[ 'name'=> $rept11],
                            
							
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
					$("#sdate,#edate").datepicker({
						format: "yyyy-mm-dd",
						language: "zh-TW"
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
