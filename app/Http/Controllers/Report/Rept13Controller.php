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

class Rept13Controller extends Controller
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
        $this->hrefMain         = 'report_13';

        $this->pageTitleMain    = '工程案件進出異常清單(明細)';//大標題
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
        $aproc     = SHCSLib::getCode('DOOR_INOUT_TYPE2',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號
		$did       ='';
		$eid       ='';
		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $eid ='';
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
            $SQL = "SELECT B.name AS 廠區,C.name AS 門別,D.name AS 廠商名稱,
					A.name AS 承攬商人員,A.door_type AS 進出狀態,convert(varchar(19),A.door_stamp,120) AS 進出時間,a.door_memo AS 進出結果,A.img_path
					FROM dbo.log_door_inout A
					JOIN dbo.b_factory B ON A.b_factory_id=B.id
					JOIN dbo.b_factory_d C ON A.b_factory_d_id=C.id
					JOIN dbo.b_supply D ON a.b_supply_id=D.id
					JOIN dbo.e_project E ON A.e_project_id=E.id
					WHERE A.door_result='N' AND A.door_date BETWEEN :sdate AND :edate AND :project_no IN (E.project_no,'') AND :project_name IN (E.name,'')
					";
			
            $listAry = DB::select($SQL,['sdate'=>$sdate,'edate'=>$edate,'project_no'=>$eid,'project_name'=>$did]);
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
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $form->addRowCnt($html);


        $html = $form->text('did',$did,2,'案名'); 
		$html.= $form->text('eid',$eid,2,'案號'); 

		
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
      
		
		//$html = '統計人數：'.count($listAry);
		//$form->addRow($html,8,0);
		
		$form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
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
