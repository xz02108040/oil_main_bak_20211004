<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\TableLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use DB;

class Rept29Controller extends Controller
{
    use SessTraits;
    /*
    |--------------------------------------------------------------------------
    | Rept1Controller
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
        $this->hrefMain         = 'report_29';

        $this->pageTitleMain    = '工程案件逾期分析報表';//大標題
        $this->pageTitleList    = '列表標題';//列表

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

        $sdate     = $request->sdate;
        $edate     = $request->edate;
        $aid       = $request->aid; //廠區
        $bid       = $request->bid; //承商
        $cid       = $request->cid; //

        //清除搜尋紀錄
        if($request->has('clear'))
        {
            $sdate = $edate = $aid = $bid = $cid = '';
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
            $SQL = "SELECT A.project_no,A.name AS projectname,B.name 分類,
						CASE WHEN A.aproc='A' THEN '停工階段'
							 WHEN A.aproc='P' THEN '施工階段'
							 WHEN A.aproc='O' THEN '結案階段'
							 WHEN A.aproc='X' THEN '作廢'
							 WHEN A.aproc='C' THEN '過期階段'
							 WHEN A.aproc='R' THEN '延長工期'
							 WHEN A.aproc='B' THEN '停工後復工'
						END aproc,CONVERT(VARCHAR,A.sdate)+'-'+CONVERT(VARCHAR,A.edate) AS date,
						E.name AS deptname,C.name chargename,D.name AS supplyname,D.boss_name,D.tel1
					FROM dbo.e_project A
					JOIN dbo.e_project_type B ON A.project_type=B.id
					JOIN dbo.b_cust C ON A.charge_user=C.id
					JOIN dbo.b_supply D ON A.b_supply_id=D.id
					JOIN dbo.be_dept E ON A.charge_dept=E.id";
            if($aid)
            {
                $SQL .= " AND id = '".$aid."'";
            }
            if($bid)
            {
                $SQL .= " AND id = '".$bid."'";
            }
            if($cid)
            {
                $SQL .= " AND id = '".$cid."'";
            }

            $listAry = DB::select($SQL);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = '';
        $html.= $form->date('sdate',$sdate,1,'開始日期');
        $html.= $form->date('edate',$edate,1,'結束日期');
        $form->addRowCnt($html);

        $html = $form->select('aid',$storeAry,$aid,2,'廠區'); //下拉選擇
        $html.= $form->select('bid',$supplyAry,$bid,2,'承攬商');
        $html.= $form->text('cid',$cid,2,'姓名'); //文字input
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search'); //搜尋按鈕
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','',''); //清除搜尋
        $form->addRowCnt($html);
        //至少一個搜尋條件
        $html = HtmlLib::Color('說明：請至少一個搜尋條件(廠區＆承商)','red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);

        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
		$heads[] = ['title'=>'工程案號'];
        $heads[] = ['title'=>'案件名稱'];
		$heads[] = ['title'=>'案件分類'];
		$heads[] = ['title'=>'工程進度'];
		$heads[] = ['title'=>'工程期間'];
		$heads[] = ['title'=>'監造單位'];
		$heads[] = ['title'=>'監造名稱'];
		$heads[] = ['title'=>'承攬商'];
		$heads[] = ['title'=>'負責人'];
		$heads[] = ['title'=>'電話'];
		
        $table->addHead($heads,0);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $rept1           = $value->project_no;
                $rept2           = $value->projectname;
				$rept3           = $value->分類;
				$rept4           = $value->aproc;
				$rept5           = $value->date;
				$rept6           = $value->deptname;
				$rept7           = $value->chargename;
				$rept8           = $value->supplyname;
				$rept9           = $value->boss_name;
				$rept10           = $value->tel1;
				
                
				
                

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
