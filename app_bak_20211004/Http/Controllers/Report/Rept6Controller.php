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

class Rept6Controller extends Controller
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
        $this->hrefMain         = 'report_6';

        $this->pageTitleMain    = '承攬商累計工時報表';//大標題
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

        $supply_id = $b_cust_id = 0;
        $param     = '?';
        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //門別
		$did       = $request->did; //統編
		$fid       = $request->fid; //姓名
        $url_supply_id = $request->supply_id; //承商
        if($url_supply_id) $supply_id = SHCSLib::decode($url_supply_id);
        if($url_supply_id) $param .= 'supply_id='.$url_supply_id;
        $url_cust_id = $request->uid; //承商
        if($url_cust_id) $b_cust_id = SHCSLib::decode($url_cust_id);
        if($url_cust_id) $param .= '&uid='.$url_cust_id;

		
        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = $did = $fid = '';
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
		if(!$did)
        {
            $did = Session::get($this->hrefMain.'.search.did','');
        } else {
            Session::put($this->hrefMain.'.search.did',$did);
        }
		if(!$fid)
        {
            $fid = Session::get($this->hrefMain.'.search.fid','');
        } else {
            Session::put($this->hrefMain.'.search.fid',$fid);
        }
        //view元件參數
        $tbTile   = $this->pageTitleList; //列表標題
        $hrefMain = $this->hrefMain.$param; //路由
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$supply_id) {
            $query = !empty($fid) ? " AND C.name LIKE '%$fid%' ":"";
            $SQL = "SELECT A.b_supply_id,B.id,B.name,:sdate1 +'～' + :edate1 AS 進場期間,
					convert(varchar,SUM(second)/3600)+'小時'+convert(varchar,SUM(second)%3600/60)+'分鐘' AS 進出總工時 
					FROM dbo.log_Working_hours A
					JOIN dbo.b_supply B ON A.b_supply_id=B.id 
                    JOIN b_cust C ON A.b_cust_id=C.id 
					WHERE A.door_date BETWEEN :sdate AND :edate AND :supplyname in (b.id,'') and :tax_num in (B.tax_num,'')
                    $query
					GROUP BY A.b_supply_id,B.id,B.name ";
            $test = 'A';
            $listAry = DB::select($SQL,['sdate'=>$sdate,'edate'=>$edate,'supplyname'=>$bid,'tax_num'=>$did,'sdate1'=>$sdate,'edate1'=>$edate]);
        }elseif($supply_id && !$b_cust_id) {
            $query = !empty($fid) ? " AND D.name LIKE '%$fid%' ":"";
            $SQL = "SELECT A.name,A.b_cust_id,B.id,B.name as supply,:sdate1 +'～' + :edate1 AS 進場期間,
					concat(left(bc_id,3), '*****' ,right(bc_id,2)) AS bc_id,rfid_code,
					convert(varchar,SUM(second)/3600)+'小時'+convert(varchar,SUM(second)%3600/60)+'分鐘' AS 進出總工時 
					FROM dbo.log_Working_hours A
					JOIN dbo.b_supply B ON A.b_supply_id=B.id 
					join b_cust_a C on A.b_cust_id=C.b_cust_id 
                    join b_cust D on A.b_cust_id=D.id 
					WHERE A.door_date BETWEEN :sdate AND :edate AND :supplyname in (b.id,'') and :tax_num in (B.tax_num,'') and A.b_supply_id = '".$supply_id."'
                    $query
					GROUP BY A.name,A.b_cust_id,B.name,B.id,bc_id,rfid_code ";
            $test = 'B';
            $listAry = DB::select($SQL,['sdate'=>$sdate,'edate'=>$edate,'supplyname'=>$bid,'tax_num'=>$did,'sdate1'=>$sdate,'edate1'=>$edate]);
        }else {
            $SQL = "SELECT A.name,B.name as supply,concat(left(bc_id,3), '*****' ,right(bc_id,2)) AS bc_id,rfid_code,
					convert(varchar,A.door_enter_time,120) AS 進場時間,convert(varchar,A.door_exit_time,120) AS 出場時間,
					convert(varchar,second/3600)+'小時'+convert(varchar,second%3600/60)+'分鐘' AS 進出總工時 
					FROM dbo.log_Working_hours A
					JOIN dbo.b_supply B ON A.b_supply_id=B.id
					join b_cust_a C on A.b_cust_id=C.b_cust_id 
					WHERE A.door_date BETWEEN :sdate AND :edate AND A.b_supply_id = '".$supply_id."'
					AND A.b_cust_id = '".$b_cust_id."' 
					order by door_enter_time";
            $test = 'C';
            $listAry = DB::select($SQL,['sdate'=>$sdate,'edate'=>$edate]);
        }

						
        //Excel
        if($request->has('download'))
        {
            //dd($test,$SQL,$supply_id,$b_cust_id,$listAry);
            //dd($request->all());
            $excelReport = [];
            if($supply_id && !$b_cust_id)
            {
                $excelReport[] = ['承攬商','身分證號','卡號','姓名','進場期間','總工時'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->supply;
					$tmp[]  = $value->bc_id;
					$tmp[]  = $value->rfid_code;
                    $tmp[]  = $value->name;
					$tmp[]  = $value->進場期間;
                    $tmp[]  = $value->進出總工時;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
            } elseif($supply_id && $b_cust_id) {
                $excelReport[] = ['承攬商','身分證號','卡號','姓名','進場時間','出場時間','工時'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->supply;
					$tmp[]  = $value->bc_id;
					$tmp[]  = $value->rfid_code;
                    $tmp[]  = $value->name;
					$tmp[]  = $value->進場時間;
					$tmp[]  = $value->出場時間;
                    $tmp[]  = $value->進出總工時;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
            } else {
                $excelReport[] = ['承攬商','進場期間','工時'];
                foreach ($listAry as $value)
                {
                    $tmp    = [];
                    $tmp[]  = $value->name;
                    $tmp[]  = $value->進場期間;
                    $tmp[]  = $value->進出總工時;
                    $excelReport[] = $tmp;
                    unset($tmp);
                }
            }

            Session::put('download.exceltoexport',$excelReport);
            return Excel::download(new ExcelExport(), '範例_'.date('Ymdhis').'.xlsx');
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,2,'開始日期');
        $html.= $form->date('edate',$edate,2,'結束日期');
        $form->addRowCnt($html);

        $html = $form->select('bid',$supplyAry,$bid,2,'承攬商');
		$html = $form->text('did',$did,2,'統編');
		$html.= $form->text('fid',$fid,2,'姓名');


        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_29'),'3','download'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        if($supply_id)
        {
            $param = ($supply_id && $b_cust_id)? '?supply_id='.SHCSLib::encode($supply_id) : '';
            $html.= $form->linkbtn($this->hrefMain.$param, '返回',1); //返回
        }
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
        if($supply_id)
        {
			$heads[] = ['title'=>'身分證號'];
			$heads[] = ['title'=>'卡號'];
            $heads[] = ['title'=>'姓名'];
        }
		if($b_cust_id)
		{
			$heads[] = ['title'=>'進場時間'];
			$heads[] = ['title'=>'出場時間'];				
		}else {
			$heads[] = ['title'=>'進場期間'];
		}
        $heads[] = ['title'=>'進出總工時'];
        $hasFun = (!$supply_id || !$b_cust_id)? 1 : 0;
        $table->addHead($heads,$hasFun);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id              = isset($value->id)? $value->id : 0;
                $uid             = isset($value->b_cust_id)? $value->b_cust_id : 0;
                $rept1           = $value->name;
				
                $rept3           = $value->進出總工時;
                $rept4           = isset($value->supply)? $value->supply : '';
//                dd($value);
                if(!$supply_id)
                {
					$rept2           = $value->進場期間;
					
                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','supply_id='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_27'),2); //下一層按鈕
					
                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '3'=>[ 'name'=> $rept3],
                        '99'=>[ 'name'=> $btn],
                    ];
                } elseif($supply_id && !$b_cust_id) {
					
					$rept2           = $value->進場期間;
					$rept6           = $value->bc_id;
					$rept7           = $value->rfid_code;
					
                    $param    = ($uid)? '&uid='.SHCSLib::encode($uid) : '';
                    $btn      = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','supply_id='.SHCSLib::encode($id).$param),Lang::get('sys_btn.btn_27'),2); //下一層按鈕
                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '4'=>[ 'name'=> $rept4],
						'6'=>[ 'name'=> $rept6],
						'7'=>[ 'name'=> $rept7],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
                        '3'=>[ 'name'=> $rept3],
                        '99'=>[ 'name'=> $btn],
                    ];
                } else {
					
					$rept2           = $value->進場時間;
					$rept5           = $value->出場時間;
					$rept6           = $value->bc_id;
					$rept7           = $value->rfid_code;
					
                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '4'=>[ 'name'=> $rept4],
						'6'=>[ 'name'=> $rept6],
						'7'=>[ 'name'=> $rept7],
                        '1'=>[ 'name'=> $rept1],
                        '2'=>[ 'name'=> $rept2],
						'5'=>[ 'name'=> $rept5],
                        '3'=>[ 'name'=> $rept3],
                    ];
                }

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
