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
use App\Model\Engineering\e_project_type;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept28Controller extends Controller
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
        $this->hrefMain         = 'report_28';

        $this->pageTitleMain    = '工程案件報表';//大標題
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
        $supplyAry = b_supply::getSelect();       //承攬商陣列
        $storeAry  = b_factory::getSelect();      //廠區陣列
		$bfactory = b_factory_d::getSelect();     //門別陣列
		$typeAry  = e_project_type::getSelect();  //案件分類陣列
        $aprocAry = SHCSLib::getCode('ENGINEERING_APROC',1);
        $datemenu  = array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $months    = $request->months;
		$years     = $request->years;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號
        $fid       = $request->fid; //承商名稱
        $gid       = $request->gid; //案件名稱
        $hid       = $request->hid; //日期選單
        $iid       = $request->iid; //案件分類
        $jid       = $request->jid; //工程進度

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $fid = $gid = $hid = $iid = $jid = '';
            Session::forget($this->hrefMain.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate','');
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate','');
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
            $iid = Session::get($this->hrefMain.'.search.iid',0);
        } else {
            Session::put($this->hrefMain.'.search.iid',$iid);
        }
        if(!$jid)
        {
            $jid = Session::get($this->hrefMain.'.search.jid',0);
        } else {
            Session::put($this->hrefMain.'.search.jid',$jid);
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
            // 查詢 sql
            $query = "";
            if($hid=='1')
			{
                if(!empty($sdate) && !empty($edate)) {
                    $query = " AND A.sdate BETWEEN '$sdate' AND '$edate' ";
                } else if (!empty($sdate)) {
                    $query = " AND A.sdate >= '$sdate' ";
                } else if (!empty($edate)) {
                    $query = " AND A.sdate <= '$edate' ";
                }
			}
			else if($hid=='2')
			{
				$query .= " AND convert(varchar(7),A.sdate,120)= '".$months."'";
			}else if($hid='3')
			{
				$query .= " AND DATEPART(YEAR,A.sdate)='".$years."'";
			}
            if (!empty($fid)) { // 承攬商名稱
                $query .= " AND D.name LIKE '%$fid%' "; 
            }
            // 完整查詢 sql
            $SQL = "SELECT A.project_no,A.name AS projectname,B.name 分類,
                        CASE WHEN A.aproc='A' THEN '停工階段'
							 WHEN A.aproc='P' THEN '施工階段'
							 WHEN A.aproc='O' THEN '結案階段'
							 WHEN A.aproc='X' THEN '作廢'
							 WHEN A.aproc='C' THEN '過期階段'
							 WHEN A.aproc='R' THEN '延長工期'
							 WHEN A.aproc='B' THEN '停工後復工'
						END aproc,
						CONVERT(VARCHAR,A.sdate)+'～'+CONVERT(VARCHAR,A.edate) AS date,
						E.name AS deptname,C.name chargename,D.name AS supplyname,D.boss_name,D.tel1,C.id, F.tel1 AS chargetel
					FROM dbo.e_project A
					JOIN dbo.e_project_type B ON A.project_type=B.id
					JOIN dbo.b_cust C ON A.charge_user=C.id
					JOIN dbo.b_supply D ON A.b_supply_id=D.id
					JOIN dbo.be_dept E ON A.charge_dept=E.id
                    JOIN dbo.b_cust_a F ON A.charge_user=F.b_cust_id
					WHERE :aid IN (A.b_factory_id,'') AND '' IN (b.id,'') AND :eid IN (A.project_no,'')
                    $query AND :bid IN (D.id, '') AND :did IN (D.tax_num, '') AND :iid IN (B.id, '')
					AND :gid IN (A.name,'') AND :jid IN (A.aproc,'')
					";

            $listAry = DB::select($SQL, ['aid' => $aid, 'bid' => $bid, 'did' => $did, 'eid' => $eid, 'gid' => $gid, 'iid' => $iid, 'jid' => $jid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','案件名稱','案件分類','工程進度','工程期間','監造單位','監造名稱','監造電話','承攬商','負責人','電話'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->projectname;
					$tmp[]  = $value->分類;
					$tmp[]  = $value->aproc;
					$tmp[]  = $value->date;
					$tmp[]  = $value->deptname;
					$tmp[]  = $value->chargename;
                    $tmp[]  = $value->chargetel;
                    $tmp[]  = $value->supplyname;
					$tmp[]  = $value->boss_name;
					$tmp[]  = $value->tel1;
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

        $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
        $html.= $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('fid',$fid,2,'承攬商名稱');
        $html.= $form->select('iid',$typeAry,$iid,2,'案件分類');
		$form->addRowCnt($html);

        $html= $form->text('did',$did,2,'統編'); 
		$html.= $form->text('eid',$eid,2,'工程案號');
        $html.= $form->text('gid',$gid,2,'案件名稱');
        $html.= $form->select('jid',$aprocAry,$jid,2,'工程進度');
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
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
        $heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'案件名稱'];
		$heads[] = ['title'=>'案件分類'];
		$heads[] = ['title'=>'工程進度'];
		$heads[] = ['title'=>'工程期間'];
		$heads[] = ['title'=>'監造單位'];
		$heads[] = ['title'=>'監造名稱'];
        $heads[] = ['title'=>'監造電話'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'負責人'];
		$heads[] = ['title'=>'電話'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2           = $value->projectname;
				$rept3           = $value->分類;
				$rept4           = $value->aproc;
				$rept5           = $value->date;
				$rept6           = $value->deptname;
				$rept7           = $value->chargename;
                $rept8           = $value->chargetel;
				$rept9           = $value->supplyname;
				$rept10           = $value->boss_name;
				$rept11           = $value->tel1;
                // F.tel1 AS chargetel, F.tel_area1 AS chargesubtel
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
