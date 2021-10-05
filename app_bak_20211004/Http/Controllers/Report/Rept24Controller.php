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
use App\Model\Engineering\et_course;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;
use Excel;

class Rept24Controller extends Controller
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
        $this->hrefMain         = 'report_24';

        $this->pageTitleMain    = '承攬商教育訓練合格名冊';//大標題
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
        $courseAry = et_course::getSelect();//課程陣列
        // b_c
        $aproc     = SHCSLib::getCode('RP_SUPPLY_CAR_APROC',0);

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $bid       = $request->bid; //承商
		$did       = $request->did; //統編
		$eid       = $request->eid; //案號
        $fid       = $request->fid; //承商名稱
        $gid       = $request->gid; //教育訓練課程

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $bid = $did = $eid = $fid = $gid = '';
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
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$fid)
        {
            $fid = Session::get($this->hrefMain.'.search.fid','');
        } else {
            Session::put($this->hrefMain.'.search.fid',$fid);
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
        if(!$gid)
        {
            $gid = Session::get($this->hrefMain.'.search.gid',0);
        } else {
            Session::put($this->hrefMain.'.search.gid',$gid);
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
            $query = "";
            if (!empty($fid)) { // 承攬商名稱
                $query .= " AND E.name LIKE '%$fid%' "; 
            }
            $SQL = "SELECT E.name AS supplyname,B.name,B.account,G.name AS course_name,
					A.pass_date,A.valid_date 
					FROM dbo.et_traning_m A 
                    JOIN dbo.b_cust B ON A.b_cust_id=B.id
					JOIN dbo.b_supply E ON A.b_supply_id=E.id
                    LEFT JOIN  dbo.e_project F ON A.e_project_id=F.id
                    JOIN et_course G ON A.et_course_id=G.id
					WHERE A.pass_date BETWEEN :sdate AND :edate AND A.aproc = 'O' -- 只查訓練合格的資料
                    AND :bid in (A.b_supply_id, '') AND :did IN (E.tax_num, '')
                    AND :eid IN (F.project_no, '') AND :gid IN (A.et_course_id, '')
                    $query
					";
                    



            //if($sdate && $edate)
            //{
            //	$SQL .= "A.door_date BETWEEN '".$sdate"' AND '".$edate."'";
            //}
            //if($aid)
            //{
            //    $SQL .= " WHERE '".$aid."' in (A.b_factory_id,'')";
            //}
            //if($bid && $aid)
            //{
            //    $SQL .= " AND B.id = '".$bid."'";
            //}else if($bid)
            //{
            //	$SQL .= " WHERE B.id= '".$bid."'";
            //}
            //if($cid && ($aid || $bid ))
            //{
            //    $SQL .= " AND '".$cid."' in (A.b_factory_d_id,'') ";
            //}else if($cid)
            //{
            //	$SQL .= " WHERE '".$cid."' in (A.b_factory_d_id,'')";
            //}


            $listAry = DB::select($SQL, ['sdate' => $sdate, 'edate' => $edate, 'bid' => $bid, 'did' => $did, 'eid' => $eid, 'gid' => $gid]);
            //Excel
            if($request->has('download'))
            {
                $excelReport = [];
                $excelReport[] = ['承攬商', '姓名', '身分證字號', '教育訓練課程','教育訓練日期','教育訓練結果','教育訓練到期日'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
					$tmp[]  = $value->supplyname;
                    $tmp[]  = $value->name;
                    $tmp[]  = substr($value->account,0,3) . '*****' . substr($value->account,-2);
                    $tmp[]  = $value->course_name;
                    $tmp[]  = $value->pass_date;
                    $tmp[]  = "合格";
                    $tmp[]  = $value->valid_date;
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
        $html.= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $html.= $form->select('gid',$courseAry,$gid,2,'教育訓練課程');
        $form->addRowCnt($html);

        $html = $form->select('bid',$supplyAry,$bid,2,'承攬商');
        // $html .= $form->text('fid',$fid,2,'承攬商名稱'); 
        $html .= $form->text('did',$did,2,'統編'); 
		$html .= $form->text('eid',$eid,2,'案號'); 
        $form->addRowCnt($html);

        $html = '<div style="text-align:right;margin-right: 15px;margin-top: 15px;">';
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
        $heads[] = ['title'=>'承攬商'];
        $heads[] = ['title'=>'姓名'];
        $heads[] = ['title'=>'身分證字號'];
		$heads[] = ['title'=>'教育訓練課程'];
        $heads[] = ['title'=>'教育訓練日期'];
		$heads[] = ['title'=>'教育訓練結果'];
		$heads[] = ['title'=>'教育訓練到期日'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->supplyname;
				$rept2           = $value->name;
                $rept3           = substr($value->account,0,3) . '*****' . substr($value->account,-2);
                $rept4           = $value->course_name;
				$rept5           = $value->pass_date;
                $rept6           = "合格";
                $rept7           = $value->valid_date;

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
