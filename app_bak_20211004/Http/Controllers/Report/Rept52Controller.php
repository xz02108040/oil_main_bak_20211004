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

class Rept52Controller extends Controller
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
        $this->hrefMain         = 'report_52';

        $this->pageTitleMain    = '廠商人員進出逾期表';//大標題
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
        $cid       = $request->cid; //姓名
		$did       = $request->did; //案名
		$eid       = $request->eid; //案號
        $hid       = $request->hid; //日期選單

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid = $hid = '';
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
        $dt = new DateTime($sdate);
        $dt->modify('+1 day');
        $edate = $dt->format('Y-m-d');

        $SQL = "SELECT A.id, A.b_cust_id, A.name, A.job_kind, A.door_stamp, B.project_no, B.name AS project_name, C.name AS supply_name, A.door_memo,
             B.project_type, A.door_type
            FROM dbo.log_door_inout A 
            JOIN dbo.e_project B ON B.id=A.e_project_id
            JOIN dbo.b_supply C ON C.id=A.b_supply_id
            WHERE door_result='Y' AND A.door_date BETWEEN '$sdate' AND '$edate'
            ORDER BY A.b_cust_id ASC, A.door_stamp ASC";

        $custInOutAry = DB::select($SQL);

        $listAry = array();

        // 找出最早的進廠紀錄
        foreach ($custInOutAry as $custIn) {
            if (!in_array($custIn->door_type, [1, 3])) {
                continue;
            }

            if (strtotime($custIn->door_stamp) > strtotime("$sdate 23:59:59")) {
                continue;
            }

            if (!isset($listAry[$custIn->b_cust_id])) {
                $custIn->violation = true;
                $custIn->door_in_stamp = $custIn->door_stamp;
                $custIn->door_out_stamp = "";

                // 找出離廠紀錄
                foreach ($custInOutAry as $custOut) {
                    if ($custIn->b_cust_id != $custOut->b_cust_id) {
                        continue;
                    }

                    if (strtotime($custOut->door_stamp) <= strtotime($custIn->door_in_stamp) + 5 * 60) { // 若門禁紀錄在刷入時間延長 5分之前則跳過 (因刷入有可能重複感應造成短時間多筆紀錄，所以最早刷入時間的後 5分內的紀錄也不計)
                        continue;
                    }

                    if (strtotime($custOut->door_stamp) <= strtotime("$sdate 23:59:59")) { // 若紀錄為查詢當天的紀錄則持續找到最晚的離廠紀錄
                        if (in_array($custOut->door_type, [2, 4])) {
                            $custIn->door_out_stamp = $custOut->door_stamp;
                        }
                    } else { // 判斷是否為跨日的離廠紀錄
                        if (!empty($custIn->door_out_stamp)) { // 已有找到查詢當日的離廠紀錄則跳出，不需再尋找跨日的紀錄
                            break;
                        }

                        if (in_array($custOut->door_type, [1, 3])) { // 若跨日的第一筆紀錄仍是進廠紀錄則視為沒有離場時間 (可能前日忘記刷或其他原因沒有紀錄，若後續有離廠紀錄是為隔日的進廠離廠，而不是查詢日期當天的進廠離廠)
                            break;
                        }

                        if (in_array($custOut->door_type, [2, 4])) { // 若跨日的第一筆必需為離廠紀錄才是本次的離廠時間
                            $custIn->door_out_stamp = $custOut->door_stamp;
                        }
                    }
                }

                // 判斷是否違規
                if ($custIn->project_type == 1) { // 若案件分類需要開許可證，則超過下午 6點即異常
                    if (!empty($custIn->door_out_stamp) && strtotime($custIn->door_out_stamp) <= strtotime("$sdate 18:00:00")) {
                        $custIn->violation = false;
                    }

                    if (empty($custIn->door_out_stamp) && time() <= strtotime("$sdate 18:00:00")) { // 若無刷出紀錄，若當前時間早於查詢日的下午 6點，有可能仍在施工尚未刷出故不算違規
                        $custIn->violation = false;
                    }
                } else if ($custIn->project_type == 2) { // 若案件分類不需要開許可證，則進入後超過 13小時未出廠即異常
                    if ((strtotime($custIn->door_out_stamp))  <= (strtotime($custIn->door_in_stamp) + 13 * 60 * 60)) {
                        $custIn->violation = false;
                    }

                    if (empty($custIn->door_out_stamp) && time() <= (strtotime($custIn->door_in_stamp) + 13 * 60 * 60)) { // 若無刷出紀錄，若當前時間早於查詢日進入時間 +13 小時前，有可能仍在施工尚未刷出故不算違規
                    }
                }

                if ($custIn->violation) {
                    $listAry[$custIn->b_cust_id] = $custIn;
                }
            }
        }

        //Excel
        if ($request->has('download')) {
            $excelReport = [];
            $excelReport[] = ['承攬商', '案件編號', '案件名稱', '姓名', '身分', '進廠時間', '離廠時間', '備註'];
            foreach ($listAry as $value) {
                $tmp    = [];
                $tmp[]  = $value->supply_name;
                $tmp[]  = $value->project_no;
                $tmp[]  = $value->project_name;
                $tmp[]  = $value->name;
                $tmp[]  = $value->job_kind;
                $tmp[]  = substr($value->door_in_stamp, 0, -4);
                $tmp[]  = substr($value->door_out_stamp, 0, -4);
                $tmp[]  = $value->door_memo;
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
        $html.= $form->date('sdate',$sdate,2,'日期');
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);

		$form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'承攬商'];
        $heads[] = ['title'=>'案件編號'];
        $heads[] = ['title'=>'案件名稱'];
        $heads[] = ['title'=>'姓名'];
        $heads[] = ['title'=>'身分'];
		$heads[] = ['title'=>'進廠時間'];
        $heads[] = ['title'=>'離廠時間'];
        $heads[] = ['title'=>'備註'];
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->supply_name;
                $rept2           = $value->project_no;
                $rept3           = $value->project_name;
                $rept4           = $value->name;
                $rept5           = $value->job_kind;
                $rept6           = substr($value->door_in_stamp, 0, -4);
                $rept7           = substr($value->door_out_stamp, 0, -4);
                $rept8           = $value->door_memo;

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
