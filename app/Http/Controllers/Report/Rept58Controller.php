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

class Rept58Controller extends Controller
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
        $this->hrefMain         = 'report_58';

        $this->pageTitleMain    = '車牌歷程查詢';//大標題
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
		$bfactory = b_factory_d::getSelect(); //門別陣列
        $aproc     = array_merge(array(0 => '請選擇'), SHCSLib::getCode('JOB_KIND', 0));
        $datemenu  =array('0'=>'請選擇','1'=>'日期區間','2'=>'年度月份','3'=>'年度');
		
        $aid       = $request->aid; // 車牌

        //清除搜尋紀錄
        if ($request->has('clear')) {
            $aid = $bid = '';
            Session::forget($this->hrefMain . '.search');
        }

        if (!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid', '');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
		
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = array();
        if (!empty($aid)) {
            $SQL = "SELECT B.car_no, C.name AS supply_name, D.project_no, D.name AS project_name, B.car_memo, B.sdate,
                       CONCAT(A.door_sdate, ' ~ ', A.door_edate) AS period, A.isClose, A.close_stamp
                    FROM dbo.e_project_car A
                    JOIN dbo.b_car B ON B.id=A.b_car_id
                    JOIN dbo.b_supply C ON C.id=A.b_supply_id
                    JOIN dbo.e_project D ON D.id=A.e_project_id
                    WHERE UPPER(REPLACE (B.car_no , '-' , '')) LIKE UPPER(REPLACE ('%$aid%' , '-' , ''))"; // 用排除 - 號及全大寫查詢

            $listAry = DB::select($SQL);
        }

        //Excel
        if ($request->has('download')) {
            $excelReport = [];
            $excelReport[] = ['車牌', '承攬商', '案件編號', '案件名稱','通行證號', '發證日', '進出期限', '狀態','停用時間'];
            foreach ($listAry as $value) {
                $tmp    = [];
                $tmp[]  = $value->car_no;
                $tmp[]  = $value->supply_name;
                $tmp[]  = $value->project_no;
                $tmp[]  = $value->project_name;
                $tmp[]  = $value->car_memo;
                $tmp[]  = $value->sdate;
                $tmp[]  = $value->period;
                $tmp[]  = $value->isClose == 'N' ? '啟用' : '停用';
                $tmp[]  = $value->close_stamp;
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
        $html .= $form->text('aid', $aid, 2, '車牌');
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //下載按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $form->addRowCnt($html);

		$form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>'車牌'];
        $heads[] = ['title'=>'承攬商'];
        $heads[] = ['title'=>'案件編號'];
        $heads[] = ['title'=>'案件名稱'];
        $heads[] = ['title'=>'通行證號'];
        $heads[] = ['title'=>'發證日'];
        $heads[] = ['title'=>'進出期限'];
        $heads[] = ['title'=>'狀態'];
        $heads[] = ['title'=>'停用時間'];

        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->car_no;
                $rept2           = $value->supply_name;
                $rept3           = $value->project_no;
                $rept4           = $value->project_name;
                $rept5           = $value->car_memo;
                $rept6           = $value->sdate;
                $rept7           = $value->period;
                $rept8           = $value->isClose == 'N' ? '啟用' : '停用';
                $rept9           = $value->close_stamp;

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
