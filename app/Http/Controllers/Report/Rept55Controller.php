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
use DateTime;
use Illuminate\Support\Facades\App;

class Rept55Controller extends Controller
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
        $this->hrefMain         = 'report_55';

        $this->pageTitleMain    = '違規申訴歷程';//大標題
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
        $aid       = $request->aid; // 案號
        $bid       = $request->bid; // 案名
        $cid       = $request->cid; // 承商
        $did       = $request->did; // 姓名
        $hid       = $request->hid; // 日期選單

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $months = $years = $aid = $bid = $cid = $did = $hid = '';
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
            $cid = Session::get($this->hrefMain.'.search.cid',0);
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did','');
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
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

        $query = '';
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
        $SQL = "SELECT B.name AS project_name, B.project_no, C.name AS supply_name, D.name AS cust_name, E.name AS charge_name,
                convert(varchar,A.apply_stamp,120) AS apply_stamp, A.violation_record1, A.violation_record3, A.limit_edate1, A.limit_edate2,
                A.charge_user, convert(varchar,A.charge_stamp,120) AS charge_stamp, A.charge_memo, G.status_val AS aproc_name
                FROM (
                    SELECT e_project_id, b_supply_id, b_cust_id, apply_date, apply_stamp, e_violation_complain_id, violation_record1, violation_record3,limit_edate1, limit_edate2, charge_user, charge_stamp, charge_memo, updated_at
                    FROM dbo.e_violation_contractor
                    WHERE e_violation_complain_id != 0
                     UNION 
                    SELECT e_project_id, b_supply_id, b_cust_id, apply_date, apply_stamp, e_violation_complain_id, violation_record1, violation_record3,limit_edate1, limit_edate2, charge_user, charge_stamp, charge_memo, updated_at
                    FROM dbo.e_violation_contractor_history
                    ) A
                JOIN e_project B ON B.id=A.e_project_id
                JOIN b_supply C ON C.id=A.b_supply_id
                JOIN b_cust D ON D.id=A.b_cust_id
                JOIN b_cust E ON E.id=A.charge_user
                JOIN e_violation_complain F ON F.id = A.e_violation_complain_id
                JOIN sys_code G ON F.aproc = G.status_key AND G.status_code = 'COMPLAIN_APROC'
                WHERE :aid IN (B.project_no, '') AND :bid IN (B.name, '') AND :cid IN (C.id, '') AND :did IN (D.name, '') $query
                ORDER BY A.charge_stamp DESC";

        $listAry = DB::select($SQL, ['aid'=>$aid, 'bid'=>$bid, 'cid'=>$cid, 'did'=>$did]);

        //Excel
        if ($request->has('download')) {
            $excelReport = [];
            $excelReport[] = ['案件編號', '案件名稱', '承攬商', '姓名','違規時間', '原因', '罰則', '申訴前日期','申訴後日期', '申訴審查人', '申訴審查時間', '申訴審查備註', '違規申訴進度'];
            foreach ($listAry as $value) {
                $tmp    = [];
                $tmp[]  = $value->project_no;
                $tmp[]  = $value->project_name;
                $tmp[]  = $value->supply_name;
                $tmp[]  = $value->cust_name;
                $tmp[]  = $value->apply_stamp;
                $tmp[]  = $value->violation_record1;
                $tmp[]  = $value->violation_record3;
                $tmp[]  = $value->limit_edate1;
                $tmp[]  = $value->limit_edate2;
                $tmp[]  = $value->charge_name;
                $tmp[]  = $value->charge_stamp;
                $tmp[]  = $value->charge_memo;
                $tmp[]  = $value->aproc_name;
                $excelReport[] = $tmp;
                unset($tmp);
            }
            Session::put('download.exceltoexport', $excelReport);
            return Excel::download(new ExcelExport(), '範例_' . date('Ymdhis') . '.xlsx');
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html .= $form->select('hid', $datemenu, $hid, 2, '日期選單');
        $html .= '<div id="dates" style="display:block;">';
        $html .= $form->date('sdate', $sdate, 2, '開始日期');
        $html .= $form->date('edate', $edate, 2, '結束日期');
        $html .= '</div>';
        $html .= '<div id="monthss" style="display:none;">';
        $html .= $form->date('months', $months, 2, '年度月份');
        $html .= '</div>';
        $html .= '<div id="yearss" style="display:none;">';
        $html .= $form->date('years', $years, 2, '年度');
        $html .= '</div>';
        $form->addRowCnt($html);

        $html = $form->text('aid', $aid, 2, '案號');
        $html .= $form->text('bid', $bid, 2, '案名');
        $html .= $form->select('cid', $supplyAry, $cid, 2, '承攬商');
        $html .= $form->text('did', $did, 2, '姓名');
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //下載按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $html .= '</div>';
        $form->addRowCnt($html);

		$form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'案件編號'];
        $heads[] = ['title'=>'案件名稱'];
        $heads[] = ['title'=>'承攬商'];
        $heads[] = ['title'=>'姓名'];
        $heads[] = ['title'=>'違規時間'];
        $heads[] = ['title'=>'原因'];
        $heads[] = ['title'=>'罰則'];
        $heads[] = ['title'=>'申訴前日期'];
        $heads[] = ['title'=>'申訴後日期'];
        $heads[] = ['title'=>'申訴審查人'];
        $heads[] = ['title'=>'申訴審查時間'];
        $heads[] = ['title'=>'申訴審查備註'];
        $heads[] = ['title'=>'違規申訴進度'];
        
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2           = $value->project_name;
                $rept3           = $value->supply_name;
                $rept4           = $value->cust_name;
                $rept5           = $value->apply_stamp;
                $rept6           = $value->violation_record1;
                $rept7           = $value->violation_record3;
                $rept8           = $value->limit_edate1;
                $rept9           = $value->limit_edate2;
                $rept10           = $value->charge_name;
                $rept11           = $value->charge_stamp;
                $rept12           = $value->charge_memo;
                $rept13           = $value->aproc_name;

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
                            '12'=>[ 'name'=> $rept12],
                            '13'=>[ 'name'=> $rept13],
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
