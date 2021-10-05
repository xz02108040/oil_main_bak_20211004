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
use App\Model\WorkPermit\wp_work_topic_a;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;
use DateTime;
use Illuminate\Support\Facades\App;

class Rept57Controller extends Controller
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
        $this->hrefMain         = 'report_57';

        $this->pageTitleMain    = '承攬商假日施工一覽表';//大標題
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
        //路由
        $this->hrefPrint        = 'report_57_print';
        //參數
        $out = $js ='';
        $no        = 0;
        $monthFirstDay = date('Y-m-01');
        $monthEndDay = date('Y-m-t');
        $today     = date('Y-m-d');
		$tomonths  = date('Y-m');
		$toyears  = date('Y');
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect(); //門別陣列
        $aproc     = array_merge(array(0 => '請選擇'), SHCSLib::getCode('JOB_KIND', 0));
        $datemenu  =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
		
        $sdate     = $request->sdate;
        $edate     = $request->edate;
		$months    = $request->months;
		$years    = $request->years;
        $aid       = $request->aid; // 工程身分
        $bid       = $request->bid; // 案號
        $hid       = $request->hid; // 日期選單

        //清除搜尋紀錄
        if ($request->has('clear')) {
            $sdate = $edate = $months = $years = $aid = $bid = '';
            Session::forget($this->hrefMain . '.search');
        }

        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$monthFirstDay);
        } else {
            if(strtotime($sdate) > strtotime($today)) $sdate = $today;
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$monthEndDay);
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
        if (!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
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
        $query = ''; // @todo change date col name
        if($hid=='1')
        {
            $query .= " AND A.sdate BETWEEN '".$sdate."' AND '".$edate."'";
        }
        else if($hid=='2')
        {
            $query .= " AND convert(varchar(7),A.sdate,120)= '".$months."'";
        }else if($hid='3')
        {
            $query .= " AND DATEPART(YEAR,A.sdate)='".$years."'";
        }

            $SQL = "SELECT A.id, A.permit_no, B.name AS dept_name, A.wp_permit_workitem_memo, A.sdate, C.name AS supply_name,
                       E.name AS charge_dept_name, F.name AS charge_name, G.mobile1 AS charge_mobile, A.wp_permit_danger,
                       LEFT(A.sdate,4)+'年'+ISNULL(substring(CAST(A.sdate as nvarchar(10)),6,2)+'月','')+ISNULL(RIGHT(A.sdate,2)+'日','')  AS sdateName
                    FROM wp_work A
                JOIN dbo.be_dept B ON B.id=A.be_dept_id1
                JOIN dbo.b_supply C ON C.id=A.b_supply_id
                JOIN dbo.e_project D ON D.id=A.e_project_id
                JOIN dbo.be_dept E ON E.id=D.charge_dept
                JOIN dbo.b_cust F ON F.id=D.charge_user
                JOIN dbo.b_cust_a G ON G.b_cust_id=D.charge_user
                WHERE A.isHoliday = 'Y' AND A.isClose = 'N' AND A.aproc NOT IN ('A','C') $query
                ORDER BY A.permit_no ";

        $listAry = DB::select($SQL);

        //Excel
        if ($request->has('download')) {
            $excelReport = [];
            $excelReport[] = ['轄區部門', '工作內容', '施工日期', '開始時間', '結束時間', '承攬商', '監造負責人', '監造部門', '監造手機', '危險等級'];
            foreach ($listAry as $value) {
                $tmp    = [];
                $tmp[]  = $value->dept_name;
                $tmp[]  = $value->wp_permit_workitem_memo;
                $tmp[]  = $value->sdate;

                list($work_stime) = wp_work_topic_a::getTopicAns($value->id, 120);
                $tmp[]  = (!empty($work_stime)) ? $work_stime : '';

                list($work_etime) = wp_work_topic_a::getTopicAns($value->id, 121);
                $tmp[]  = (!empty($work_etime)) ? $work_etime : '';

                $tmp[]  = $value->supply_name;
                $tmp[]  = $value->charge_dept_name;
                $tmp[]  = $value->charge_name;
                $tmp[]  = $value->charge_mobile;
                $tmp[]  = $value->wp_permit_danger;
                $excelReport[] = $tmp;
                unset($tmp);
            }
            Session::put('download.exceltoexport', $excelReport);
            return Excel::download(new ExcelExport(), '範例_' . date('Ymdhis') . '.xlsx');
        }

        //print列印
        if ($request->has('print')) {
            $showAry = [];
            $showAry['today'] = date('Y').'年'.date('m').'月'.date('d').'日';
            $showAry['data'] = [];
            foreach ($listAry  as $key => $value) {
                $tmp    = [];
                $tmp[]  = $key + 1;
                $tmp[]  = $value->dept_name;
                $tmp[]  = $value->wp_permit_workitem_memo;

                list($work_stime) = wp_work_topic_a::getTopicAns($value->id, 120);
                $work_stime    = ($work_stime) ? date('H:i', strtotime($work_stime)) : '';

                list($work_etime) = wp_work_topic_a::getTopicAns($value->id, 121);
                $work_etime    = ($work_etime) ? '~' . date('H:i', strtotime($work_etime)) : '';

                $tmp[]  = $value->sdate . ' '. $work_stime .  $work_etime;
                $tmp[]  = $value->supply_name .
                $tmp[]  = $value->charge_dept_name . '/' . $value->charge_name . '/' . $value->charge_mobile;
                $tmp[]  = $value->wp_permit_danger;
                $tmp[]  = '';
                $tmp[]  = '';

                $showAry['data'][$key] = $tmp;
                unset($tmp);
            }

            return view('report.Rep57_Holiday',$showAry);

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
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //下載按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_42'), '5', 'print'); //列印按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRowCnt($html);

		$form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'轄區部門'];
        $heads[] = ['title'=>'工作內容'];
        $heads[] = ['title'=>'施工日期'];
        $heads[] = ['title'=>'開始時間'];
        $heads[] = ['title'=>'結束時間'];
        $heads[] = ['title'=>'承攬商'];
        $heads[] = ['title'=>'監造負責人'];
        $heads[] = ['title'=>'監造部門'];
        $heads[] = ['title'=>'監造手機'];
        $heads[] = ['title'=>'危險等級'];

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                //$btn             = $form->linkbtn($this->hrefPrint.'?id='.SHCSLib::encode($value->id), $value->permit_no,5,'','','','_blank'); //新增
                $rept1           = $value->dept_name;
                $rept2           = $value->wp_permit_workitem_memo;
                $rept3           = $value->sdate;

                list($work_stime) = wp_work_topic_a::getTopicAns($value->id, 120);
                $rept4           = (!empty($work_stime)) ? $work_stime : '';

                list($work_etime) = wp_work_topic_a::getTopicAns($value->id, 121);
                $rept5           = (!empty($work_etime)) ? $work_etime : '';

                $rept6           = $value->supply_name;
                $rept7           = $value->charge_dept_name;
                $rept8           = $value->charge_name;
                $rept9           = $value->charge_mobile;
                $rept10          = $value->wp_permit_danger;

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
