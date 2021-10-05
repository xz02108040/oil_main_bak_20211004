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

class Rept27Controller extends Controller
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
        $this->hrefMain         = 'report_27';

        $this->pageTitleMain    = '工程案件尚未設定承攬商成員異常報表';//大標題
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
        $supplyAry = b_supply::getSelect();  //承攬商陣列
        $storeAry  = b_factory::getSelect(); //廠區陣列
		$bfactory = b_factory_d::getSelect();//門別陣列
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; // 承攬商
        $bid       = $request->bid; // 案件編號
        $cid       = $request->cid; // 案件名稱
        $did       = $request->did; // 承攬商名稱

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = '';
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
        if(!$aid)
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
            $querySql = "";
            if (!empty($bid)) {
                $querySql .= " AND A.project_no LIKE '%$bid%' "; 
            }
            if (!empty($cid)) {
                $querySql .= " AND A.name LIKE '%$cid%' "; 
            }
            if (!empty($did)) {
                $querySql .= " AND C.name LIKE '%$did%' "; 
            }
            $SQL = "SELECT A.project_no,A.name AS projectname,C.name AS supplyname,CONVERT(VARCHAR,A.sdate)+'-'+CONVERT(VARCHAR,A.edate) AS date,D.name AS 監造部門,B.name AS 監造名稱, E.tel1 AS 監造電話
					FROM dbo.e_project A
					JOIN dbo.b_cust B ON A.charge_user=B.id
					JOIN dbo.b_supply C ON A.b_supply_id=C.id
					JOIN dbo.be_dept D ON A.charge_dept=D.id
                    JOIN dbo.b_cust_a E ON A.charge_user=E.b_cust_id
					WHERE NOT EXISTS(SELECT * FROM dbo.e_project_s WHERE e_project_id=A.id)
					AND A.isClose='N' AND C.isClose='N' AND A.aproc in ('P','R','B','A')
                    AND A.sdate BETWEEN :sdate AND :edate AND :aid IN (A.b_supply_id,'')
                    $querySql
					";

            $listAry = DB::select($SQL, ['sdate' => $sdate, 'edate' => $edate, 'aid' => $aid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['工程案號','案件名稱','承攬商','工程期間','監造單位','監造名稱', '監造電話'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->project_no;
                    $tmp[]  = $value->projectname;
					$tmp[]  = $value->supplyname;
					$tmp[]  = $value->date;
					$tmp[]  = $value->監造部門;
					$tmp[]  = $value->監造名稱;
                    $tmp[]  = $value->監造電話;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
                Session::put('download.exceltoexport',$excelReport);
                return Excel::download(new ExcelExport(), '工程案件尚未設定承攬商成員異常報表_'.date('Ymdhis').'.xlsx');
            }
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';

        $html .= $form->date('sdate', $sdate, 2, '工程開始日期');
        $html .= $form->date('edate', $edate, 2, '工程結束日期');
        $html .= $form->select('aid', $supplyAry, $aid, 2, '承攬商');
        $html .= $form->text('did', $did, 2, '承攬商名稱');
        $form->addRowCnt($html);

        $html = $form->text('bid', $bid, 2, '案件編號');
        $html .= $form->text('cid', $cid, 2, '案件名稱');
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
        $html .= $form->submit(Lang::get('sys_btn.btn_8'), '1', 'search'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_29'), '3', 'download'); //搜尋按鈕
        $html .= $form->submit(Lang::get('sys_btn.btn_40'), '4', 'clear', '', ''); //清除搜尋
        $html .= '</div>';
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
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'工程期間'];
		$heads[] = ['title'=>'監造單位'];
		$heads[] = ['title'=>'監造名稱'];
        $heads[] = ['title'=>'監造電話'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2           = $value->projectname;
				$rept3           = $value->supplyname;
				$rept4           = $value->date;
				$rept5           = $value->監造部門;
				$rept6           = $value->監造名稱;
                $rept7           = $value->監造電話;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $rept1],
                            '2'=>[ 'name'=> $rept2],
							'3'=>[ 'name'=> $rept3],
							'4'=>[ 'name'=> $rept4],
							'5'=>[ 'name'=> $rept5],
							'6'=>[ 'name'=> $rept6],
                            '7'=>[ 'name'=> $rept7],
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
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];

        return view('index',$retArray);
    }

}
