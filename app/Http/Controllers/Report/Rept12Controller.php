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

class Rept12Controller extends Controller
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
        $this->hrefMain         = 'report_12';
		$this->hrefMain1        = 'report_12a';
        //$this->hrefMain2        = 'report_12a?aid=';

        $this->pageTitleMain    = '工程案件進出異常統計';//大標題
		$this->pageTitleMain2   = '廠區進出異常清單(明細)';//大標題
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
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2',0);
		$datemenu  =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
        $doorMemoAry = array('0'=>'請選擇');
        $doorMemoResults = DB::table('log_door_inout')->where('door_memo', '!=', '')->select('door_memo')->distinct()->get();
        foreach ($doorMemoResults as $doorMemoResult) {
            $doorMemoAry[] = $doorMemoResult->door_memo;
        }

        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
		$years    = $request->years;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //案名
		$eid       = $request->eid; //案號
        $fid       = $request->fid; //刷卡異常原因
		$hid       = $request->hid; //日期選單
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $months = $years = $did = $eid = $fid = $hid = '';
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
            $fid = Session::get($this->hrefMain.'.search.fid',0);
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
            $dateSql = "";
			if($hid=='1')
			{
				$dateSql = " AND door_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$dateSql = " AND convert(varchar(7),door_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$dateSql = " AND DATEPART(YEAR,door_date)='".$years."'";
            }
					
            $SQL = "SELECT B.name AS 廠區,C.name AS 門別,a.door_memo, A.count AS 數量, D.project_no AS 案件編號, D.name AS 案件名稱,
                    B.id AS 廠區編號,C.id 門別編號 FROM (
                        SELECT e_project_id, door_memo, b_factory_id, b_factory_d_id, COUNT(*) as count
                        FROM dbo.log_door_inout
                        WHERE door_result='N' $dateSql
                        GROUP BY e_project_id, door_memo, b_factory_id, b_factory_d_id
					) A 
					JOIN dbo.b_factory B ON A.b_factory_id=B.id
					JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id
					JOIN dbo.e_project D ON a.e_project_id=D.id
					WHERE :project_no IN (D.project_no,'') AND :project_name IN (D.name,'')";
            if (!empty($fid)) {
                $SQL .= " AND A.door_memo = '" . $doorMemoAry[$fid] . "' ";
            }

            $listAry = DB::select($SQL,['project_no'=>$eid,'project_name'=>$did]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['案件編號','案件名稱','廠區','門別','刷卡異常原因','數量'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->案件編號;
                    $tmp[]  = $value->案件名稱;
					$tmp[]  = $value->廠區;
                    $tmp[]  = $value->門別;
                    $tmp[]  = $value->door_memo;
					$tmp[]  = $value->數量;
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

        $html = $form->text('eid', $eid, 2, '案號');
        $html .= $form->text('did', $did, 2, '案名');
        $html .= $form->select('fid', $doorMemoAry, $fid, 2, '刷卡異常原因');
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRowCnt($html);

		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //明細的搜尋條件
        $detailSearch = [
            'sdate' => $sdate,
            'edate' => $edate,
            'months' => $months,
            'years' => $years,
            'did' => $did,
            'eid' => $eid,
            'fid' => $fid,
            'hid' => $hid,
        ];

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'案件編號'];
        $heads[] = ['title'=>'案件名稱'];
        $heads[] = ['title'=>'廠區'];
        $heads[] = ['title'=>'門別'];
		$heads[] = ['title'=>'刷卡異常原因']; 
		$heads[] = ['title'=>'數量']; 
		$heads[] = ['title'=>'功能'];

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->案件編號;
                $rept2           = $value->案件名稱;
                $rept3           = $value->廠區;
                $rept4           = $value->門別;
				$rept5           = $value->door_memo;
                $rept6           = $value->數量;
                //$MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),3);
				
                $detailSearch['eid'] = $rept1;
                $detailSearch['fid'] = $rept5;
                $detailSearch['factory_id'] = $value->廠區編號;
                $detailSearch['factory_d_id'] = $value->門別編號;
                $detailSearchEncode = SHCSLib::encode(json_encode($detailSearch));
                $Btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','search='.$detailSearchEncode),'明細',2); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
                            '5'=>[ 'name'=> $rept5],
                            '6'=>[ 'name'=> $rept6],
							'7'=>[ 'name'=> $Btn],
							//'6'=>[ 'name'=> $MemberBtn],
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

	    public function index2(Request $request)
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
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2',0);
		$datemenu  =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
        $doorMemoAry = array('0'=>'請選擇');
        $doorMemoResults = DB::table('log_door_inout')->where('door_memo', '!=', '')->select('door_memo')->distinct()->get();
        foreach ($doorMemoResults as $doorMemoResult) {
            $doorMemoAry[] = $doorMemoResult->door_memo;
        }

        $search = json_decode(SHCSLib::decode($request->search), true);
        if (isset($search)) {
            Session::put($this->hrefMain1 . '.detail.search', $request->search); // 將查詢條件存到 Session，下載時透過 Session 抓取查詢條件
        } else {
            $searchCode = Session::get($this->hrefMain1 . '.detail.search'); // 下載時抓不到查詢條件從 Session 取得
            $search = json_decode(SHCSLib::decode($searchCode), true);
        }

        $sdate     = $search['sdate'];
        $edate     = $search['edate'];
        $months     = $search['months'];
        $years     = $search['years'];
		$did       = $search['did']; //案名
		$eid       = $search['eid']; //案號
        $fid       = $search['fid']; //刷卡異常原因
        $fid       = array_search($fid, $doorMemoAry);
        $hid       = $search['hid']; //日期選單
        $factory_id = $search['factory_id']; //廠區編號
        $factory_d_id = $search['factory_d_id']; //門別編號

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $months = $years = $did = $eid = $fid = $hid  = '';
            Session::forget($this->hrefMain1.'.search');
        }
        //進出日期
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain1.'.search.sdate',$today);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain1.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain1.'.search.edate',$today);
        } else {
            if(strtotime($edate) < strtotime($sdate)) $edate = $sdate; //如果結束日期 小於開始日期
            Session::put($this->hrefMain1.'.search.edate',$edate);
        }
		if(!$months)
        {
            $months = Session::get($this->hrefMain1.'.search.months',$tomonths);
        } else {
            //if(strtotime($months) > strtotime($tomonths)) $months = $tomonths;
			//date('Y-m-d', strtotime('+1 year'));
            Session::put($this->hrefMain1.'.search.months',$months);
        }
		if(!$years)
        {
            $years = Session::get($this->hrefMain1.'.search.years',$toyears);
        } else {
            Session::put($this->hrefMain1.'.search.years',$years);
        }
		if(!$did)
        {
            $did = Session::get($this->hrefMain1.'.search.did','');
        } else {
            Session::put($this->hrefMain1.'.search.did',$did);
        }
		if(!$eid)
        {
            $eid = Session::get($this->hrefMain1.'.search.eid','');
        } else {
            Session::put($this->hrefMain1.'.search.eid',$eid);
        }
        if(!$fid)
        {
            $fid = Session::get($this->hrefMain1.'.search.fid',0);
        } else {
            Session::put($this->hrefMain1.'.search.fid',$fid);
        }
		if(!$hid)
        {
            $hid = Session::get($this->hrefMain1.'.search.hid',1);
        } else {
            Session::put($this->hrefMain1.'.search.hid',$hid);
        }
		
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain1 = $this->hrefMain1; //路由
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
            $SQL = "SELECT B.name AS 廠區,C.name AS 門別,D.name AS 廠商名稱,
					A.name AS 承攬商人員,A.door_type AS 進出狀態,convert(varchar(19),A.door_stamp,120) AS 進出時間,a.door_memo AS 進出結果,A.img_path
					FROM dbo.log_door_inout A
					JOIN dbo.b_factory B ON A.b_factory_id=B.id
					JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id
					JOIN dbo.b_supply D ON a.b_supply_id=D.id
					JOIN dbo.e_project E ON A.e_project_id=E.id
					WHERE A.door_result='N' AND :project_no IN (E.project_no,'') AND :project_name IN (E.name,'') AND :door_memo IN (A.door_memo, '')
                    AND :factory_id IN (A.b_factory_id, '') AND :factory_d_id IN (A.b_factory_d_id, '')
					".$query."
					";

            $listAry = DB::select($SQL, ['project_no' => $eid, 'project_name' => $did, 'door_memo' => $doorMemoAry[$fid], 'factory_id'=>$factory_id,'factory_d_id'=>$factory_d_id]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['廠區','門別','承攬商','承攬商人員','進出狀態','進出時間','進出結果'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->廠區;
                    $tmp[]  = $value->門別;
                    $tmp[]  = $value->廠商名稱;
					$tmp[]  = $value->承攬商人員;
					$tmp[]  = isset($aproc[$value->進出狀態])?$aproc[$value->進出狀態]:'';
					$tmp[]  = $value->進出時間;
					$tmp[]  = $value->進出結果;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '工程案件進出異常清單_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain1,'POST','form-inline');
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

        $html = $form->text('eid', $eid, 2, '案號');
        $html .= $form->text('did', $did, 2, '案名');
        $html .= $form->select('fid', $doorMemoAry, $fid, 2, '刷卡異常原因');
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRowCnt($html);

		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain1);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'廠區'];
        $heads[] = ['title'=>'門別'];
		$heads[] = ['title'=>'承攬商']; 
		$heads[] = ['title'=>'承攬商人員']; 
		$heads[] = ['title'=>'進出狀態']; 
		$heads[] = ['title'=>'進出時間'];
		$heads[] = ['title'=>'進出結果']; 
		$heads[] = ['title'=>'照片']; 

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->廠區;
                $rept2           = $value->門別;
				$rept3           = $value->廠商名稱;
                $rept4           = $value->承攬商人員;
				$rept5           = isset($aproc[$value->進出狀態])?$aproc[$value->進出狀態]:'';
				$rept6           = $value->進出時間;
				$rept7           = $value->進出結果;
				$rept8           = $value->img_path;
				
                

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
							'5'=>[ 'name'=> $rept5],
							'6'=>[ 'name'=> $rept6],
							'7'=>[ 'name'=> $rept7],
							'8'=>[ 'name'=> $rept8],
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
