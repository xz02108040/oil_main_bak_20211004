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

class Rept10Controller extends Controller
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
        $this->hrefMain         = 'report_10';
		$this->hrefMain1         = 'report_10a';

        $this->pageTitleMain    = '廠區進出異常統計';//大標題
		$this->pageTitleMain2   = '廠區進出異常清單(明細)';//大標題
        $this->pageTitleList    = '';//列表
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');


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
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號
        $fid       = $request->fid; //承商名稱
        $gid       = $request->gid; //刷卡異常原因
		$hid       = $request->hid; //日期選單
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $hid = 1;
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $fid = $gid = '';
            Session::forget($this->hrefMain.'.search');
            Session::forget($this->hrefMain1.'.search');
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
            $querySQL = "";
			if($hid=='1')
			{
				$querySQL = " AND A.door_date BETWEEN '".$sdate."' AND '".$edate."'";
			}
			else if($hid=='2')
			{
				$querySQL = " AND convert(varchar(7),A.door_date,120)= '".$months."'";
			}else if($hid='3')
			{
				$querySQL = " AND DATEPART(YEAR,A.door_date)='".$years."'";
            }
            if (!empty($fid)) {
                $querySQL .= " AND C.name LIKE '%$fid%' ";
            }
            if (!empty($gid)) {
                $querySQL .= " AND A.door_memo LIKE '%$gid%' ";
            }
            $SQL = "SELECT B.name AS 廠區,C.name AS 門別,a.door_memo, A.count AS 數量,
                    B.id AS 廠區編號,C.id 門別編號 FROM (
                        SELECT A.door_memo, A.b_factory_id, A.b_factory_d_id, COUNT(*) as count
                        FROM dbo.log_door_inout A
                        JOIN dbo.e_project B ON A.e_project_id=B.id
                        JOIN dbo.b_supply C ON A.b_supply_id=C.id
                        WHERE A.door_result='N' AND :factory_id IN (A.b_factory_id,'') AND :factory_d_id IN (A.b_factory_d_id,'') 
                        AND :supply_id IN (C.id, '') $querySQL
                        GROUP BY A.door_memo, A.b_factory_id, A.b_factory_d_id
					) A 
					JOIN dbo.b_factory B ON A.b_factory_id=B.id
					JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id";
					
            $listAry = DB::select($SQL,['factory_id'=>$aid,'factory_d_id'=>$cid,'supply_id'=>$bid]);

            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['廠區','門別','進出結果','數量'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->廠區;
                    $tmp[]  = $value->門別;
                    $tmp[]  = $value->door_memo;
					$tmp[]  = $value->數量;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '廠區進出異常統計_'.date('Ymdhis').'.ods',\Maatwebsite\Excel\Excel::ODS);
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
        $html.= $form->text('gid',$gid,2,'刷卡異常原因');
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
		$html.= $form->select('cid',$bfactory,$cid,2,'門別');
        $html.= $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('fid',$fid,2,'承攬商名稱');
		$form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $html.= '</div>';
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
      
		
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
            'aid' => $aid,
            'bid' => $bid,
            'cid' => $cid,
            'did' => $did,
            'eid' => $eid,
            'fid' => $fid,
            'hid' => $hid,
        ];

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'廠區'];
        $heads[] = ['title'=>'門別'];
		$heads[] = ['title'=>'進出結果'];		
		$heads[] = ['title'=>'數量']; 
		$heads[] = ['title'=>'功能']; 

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->廠區;
                $rept2           = $value->門別;
                $rept3           = $value->door_memo;
                $rept4           = $value->數量;
				$rept5           = $value->廠區編號;
				$rept6           = $value->門別編號;

                $detailSearch['aid'] = $rept5;
                $detailSearch['cid'] = $rept6;
                $detailSearch['memo'] = $rept3;
                $detailSearchEncode = SHCSLib::encode(json_encode($detailSearch));
                $Btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','search='.$detailSearchEncode),'明細',2); //按鈕
                // $Btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','aid='.SHCSLib::encode($rept5).'&cid='.SHCSLib::encode($rept6).'&memo='.SHCSLib::encode($rept3)),'明細',2); //按鈕
				//$Btn             = HtmlLib::btn(SHCSLib::url($this->hrefMain1,'','aid='.$rept5.'&cid='.$rept6),'明細',2); //按鈕
                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
                            '3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
							'5'=>[ 'name'=> $Btn],
                            		
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
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2',0);
        $hrefBack  = $this->hrefMain;
        $btnBack   = $this->pageBackBtn;

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
        
		// $aid_encode   = SHCSLib::decode($request->aid);
		// $cid_encode   = SHCSLib::decode($request->cid);
        // $memo_encode   = SHCSLib::decode($request->memo);
        $aid       = $search['aid']; //廠區
        $bid       = $search['bid']; //承商
        $cid       = $search['cid']; //門別
		$did       = $search['did']; //統編
		$eid       = $search['eid']; //案號
        $fid       = $search['fid']; //承商名稱
        $hid       = $search['hid']; //日期選單
        $memo       = $search['memo']; //異常原因

        // var_dump($search);
		
		//echo   $aid_encode;
		//echo   $cid_encode;
        //清除搜尋紀錄
        // if($request->has('clear'))
        // {
        //     $sdate = $edate = $aid = $bid = $cid = '';
        //     Session::forget($this->hrefMain1.'.search');
        // }
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
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain1.'.search.aid',0);
        } else {
            Session::put($this->hrefMain1.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain1.'.search.bid',0);
        } else {
            Session::put($this->hrefMain1.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain1.'.search.cid',0);
        } else {
            Session::put($this->hrefMain1.'.search.cid',$cid);
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
            if (!empty($fid)) {
                $query .= " AND D.name LIKE '%$fid%' ";
            }
            $SQL = "SELECT A.id,B.name AS 廠區,C.name AS 門別,D.sub_name AS 廠商名稱,E.name AS 工程案件名稱,
					F.name AS 承攬商人員,A.door_type AS 進出狀態,convert(varchar(19),A.door_stamp,120) AS 進出時間,a.door_memo AS 進出結果,A.img_path
					FROM dbo.log_door_inout A
					JOIN dbo.b_factory B ON A.b_factory_id=B.id
					JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id
					JOIN dbo.b_supply D ON a.b_supply_id=D.id
					JOIN dbo.e_project E ON A.e_project_id=E.id
					JOIN dbo.b_cust F ON A.b_cust_id=F.id
					WHERE :factory_id IN (B.id,'') AND :factory_d_id IN (C.id,'') AND a.door_memo = :memo AND :supply_id IN (D.id, '')
					AND A.door_result='N' $query
					";

// $SQL = "SELECT B.name AS 廠區,C.name AS 門別,a.door_memo,COUNT(*) 數量,B.id AS 廠區編號,C.id 門別編號 FROM (
//     SELECT DISTINCT b_cust_id,name,bc_type,job_kind,e_project_id,b_supply_id,be_dept_id,
//     door_date,door_type,b_factory_id,b_factory_d_id,wp_work_id,door_memo, MIN(door_stamp) OVER (PARTITION BY door_date) 最早進門
//     ,MAX(door_stamp) OVER (PARTITION BY door_date) 最晚出門
//     FROM dbo.log_door_inout WHERE  door_result='N'
//     ) A 
//     JOIN dbo.b_factory B ON A.b_factory_id=B.id
//     JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id
//     JOIN dbo.b_supply D ON a.b_supply_id=D.id
//     WHERE :supplyname IN (D.id,'') AND :factory_id IN (B.id,'') AND :factory_d_id IN (C.id,'') ".$query."
//     GROUP BY B.name,C.name,A.door_memo,B.id,C.id";
// var_dump($aid, $cid);
// exit;

			$listAry = DB::select($SQL,['factory_id'=>$aid,'factory_d_id'=>$cid,'memo'=>$memo,'supply_id'=>$bid]);

            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['廠區','門別','承攬商','工程案件','承商人員','進出狀態','進出時間','進出結果'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->廠區;
                    $tmp[]  = $value->門別;
                    $tmp[]  = $value->廠商名稱;
					$tmp[]  = $value->工程案件名稱;
					$tmp[]  = $value->承攬商人員;
					$tmp[]  = isset($aproc[$value->進出狀態])?$aproc[$value->進出狀態]:'';
					$tmp[]  = $value->進出時間;
					$tmp[]  = $value->進出結果;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '廠區進出異常清單_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
		//$hrefMain1 = $hrefMain1.'?aid='.SHCSLib::encode($aid).'&cid='.SHCSLib::encode($cid);
		//echo $hrefMain1;
        $form = new FormLib(0,$hrefMain1,'POST','form-inline');
        $html = '';
        // $html.= $form->date('sdate',$sdate,2,'開始日期');
        // $html.= $form->date('edate',$edate,2,'結束日期');
        // $form->addRowCnt($html);

        // $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
		// $html.= $form->select('cid',$bfactory,$cid,2,'門別');
		//$html.= $form->hidden('cid',,$cid);
		
		
        // $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html = $form->submit(Lang::get('sys_btn.btn_29'),'3','download').'&nbsp;'; //下載按鈕
        $html.= $form->linkBtn($hrefBack, $btnBack, '2');
        // $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRow($html,12,0);
        //至少一個搜尋條件
        // $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        // $form->addRow($html);
      
		
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
		$heads[] = ['title'=>'工程案件']; 
		$heads[] = ['title'=>'承商人員']; 
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
                $rept4           = $value->工程案件名稱;
                $rept5           = $value->承攬商人員;
				$rept6           = isset($aproc[$value->進出狀態])?$aproc[$value->進出狀態]:'';
				$rept7           = $value->進出時間;
				$rept8           = $value->進出結果;

                $img_url = '';
                if($value->img_path)
                {
                    if(strpos($value->img_path,'http')!==false)
                    {
                        $img_url = $value->img_path;
                    } else {
                        $img_url = '/img/Door/'.SHCSLib::encode($value->id);
                    }
                }

				$rept9           = ($value->img_path)? HtmlLib::btn($img_url,'查看',3,'','','','_blank') : '';
                

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
					
             });';

        $css = '
                
             
            ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain2,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }
}
