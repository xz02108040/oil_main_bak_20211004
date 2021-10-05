<?php

namespace App\Http\Controllers;


use Auth;
use Lang;
use Excel;
use Session;
use App\Lib\LogLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Lib\TokenLib;
use App\Lib\ContentLib;
use App\Model\sys_param;
use App\Exports\ExcelExport;
use Illuminate\Http\Request;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\SessTraits;
use Illuminate\Support\Facades\DB;
use App\Model\Engineering\e_project;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;

class TestSQLController extends Controller
{
    use SessTraits,BcustTrait;
    /*
    |--------------------------------------------------------------------------
    | TestSQLController
    |--------------------------------------------------------------------------
    |
    | Test SQL
    | 高風險 限定
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
        $this->hrefHome      = '/testsql';
        $this->routerPost    = 'testsql';

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
        $ip     = $request->ip();
        $menu   = '管理員線上更新';
        $action = 2;
        $id     = 0;
        $dataReport = $titleAry = $result= [];

        //POST
        if ($request->sqltest && strlen($request->sqltest) > 10) {
            $result = \DB::select($request->sqltest);
            //Excel
            if ($request->has('download')) {

                $excelReport = [];
                foreach ($result as $key => $value) {
                    if (!$key) {
                        $excelReport[] = json_decode(collect($value)->keys(), true); //title
                    }
                    $excelReport[] = collect($value)->values(); //data
                }
                Session::put('download.exceltoexport', $excelReport);
                return Excel::download(new ExcelExport(), 'SQL TEST EXCEL' . date('Ymdhis') . '.xlsx');
            }
            //快速查詢
            elseif ($request->has('search')) {
                foreach ($result as $key => $value) {
                    //取得欄位名稱的陣列
                    if (!$key) {
                        $titleAry = json_decode(collect($value)->keys(), true); //title
                    }
                }
                $request->key = 'Httc@24508323';
            } else {
                dd($result);
            }
        }
        //更新語法
        elseif ($request->has('update')) {

            //資料不齊全
            if (!$request->sql_update) {
                return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
            } elseif (strtoupper(substr($request->sql_update, 0, 6)) != 'UPDATE') {
                // 更新失敗，更新語法開頭須為UPDATE
                return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_30179'))
                ->withInput();
            } elseif (!strpos(strtoupper($request->sql_update), ' WHERE ')) {
                // 更新失敗，必須下WHERE條件
                return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_30180'))
                ->withInput();
            }

            $ret = \DB::update($request->sql_update);
            if ($ret) {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id, $menu, $ip, $action, 'null', $id);

                //2-1-2 回報 更新成功
                Session::flash('message', Lang::get('sys_base.base_10104'));
                $request->key = 'SQL@24508323';
            }
        }
        //加密函數
        elseif($request->encode){
            $result_encode = SHCSLib::encode($request->encode);
            $request->key = 'Httc@24508323';
        }
        //解密函數
        elseif($request->decode){
            $result_decode = SHCSLib::decode($request->decode);
            $request->key = 'Httc@24508323';
        }
        //-------------------------------------------//
        //View
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        if($request->key == 'Httc@24508323')
        {
            //--- DEBUG ---//
            $html = HtmlLib::genBoxStart('DEBUG', 2);
            $form->addHtml($html);

            //SQL
            $html = $form->textarea('sqltest','','',$request->sqltest);
            $form->add('nameT1', $html,'SQL');

            //Submit
            $submitDiv  = $form->submit(Lang::get('sys_btn.btn_8'),'1','agreeY').'&nbsp;';
            $submitDiv  .= $form->submit(Lang::get('sys_btn.btn_81'),'4','search').'&nbsp;';
            $submitDiv  .= $form->submit('EXCEL'.Lang::get('sys_btn.btn_29'),'2','download').'&nbsp;';
            $form->boxFoot($submitDiv);

            // 快速查詢GridView
            if (count($titleAry)) {
                //table
                $table = new TableLib(array($this->routerPost, -1));
                //標題
                foreach ($titleAry as $title) {
                    $heads[] = ['title' => $title];
                }
                $table->addHead($heads, 0);

                if (count($result)) {
                    $data = [];
                    foreach ($result as $value) {
                        //用迴圈自動取欄位名稱與欄位值
                        foreach ($value as $column_name => $column_val) {
                            $data[$column_name] = ['name' => $column_val];
                        }
                        $tBody[] = $data;
                    }
                    $table->addBody($tBody);
                }
                //輸出
                $form->addHtml($table->output());
                unset($table);
            }

            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
             
            /* TODO
            //--- 快速查詢 ---//
            $html = HtmlLib::genBoxStart('Quick Search', 3);
            $form->addHtml($html);

            $html = $form->select('table', $this->tableAry(0,1));
            $form->add('nameT1', $html,'資料表',1);

            $html = $form->text('COLUMNS','*');
            $form->add('nameT1', $html,'欄位',1);

            $html = $form->text('Filter');
            $html .= isset($result_SQL) ? $result_SQL : '';
            $form->add('nameT1', $html,'限定條件');

            //Submit
            $submitDiv  = $form->submit(Lang::get('sys_btn.btn_8'),'1','search').'&nbsp;';
            $form->boxFoot($submitDiv);

            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
            */

            //--- SHCSLib ---//
            $html = HtmlLib::genBoxStart('SHCSLib', 4);
            $form->addHtml($html);

            //AES
            $html = $form->text('encode');
            $html .= isset($result_encode) ? $result_encode : '';
            $form->add('nameT2', $html,'加密函數');

            $html = $form->text('decode');
            $html .= isset($result_decode) ? $result_decode : '';
            $form->add('nameT2', $html,'解密函數');

            //Submit
            $submitDiv  = $form->submit(Lang::get('sys_btn.btn_8'),'1','agreeY').'&nbsp;';
            $form->boxFoot($submitDiv);

            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
        }
        $out = $form->output();
        
        //SQL UPDATE
        if($request->key == 'SQL@24508323' && $this->isRoot == 'Y')
        {
            $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
            //SQL
            $html = $form->textarea('sql_update','','',$request->sql_update);
            $form->add('nameT3', $html,'SQL',1);

            $html = HtmlLib::Color('此頁面為資料異動，請小心服用!','red',1);
            $form->add('nameT3', $html,'注意事項');

            //Submit
            $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'5','update').'&nbsp;';
            $form->boxFoot($submitDiv);

            $out3 = $form->output();
        }


        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form('HTTC', $out,1));
        if ($request->key == 'SQL@24508323') {
            $content->rowTo($content->box_form(Lang::get('sys_base.base_10025'), $out3, 5));
        }
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
            $("#table1").DataTable({
                "language": {
                "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
            }
            });

        } );';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>'SQL TEST','content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];

        return view('index',$retArray);
    } 

    //資料表下拉
    function tableAry($isView = 0,$isFirst = 0){
        $ret = array();
        $aWhere = array();
        if ($isFirst) {
            $ret[0] = Lang::get('sys_base.base_10015');
        }
        if ($isView == 0) {
            $aWhere[] = array('TABLE_TYPE','!=','VIEW');
        }
        $data = DB::table(DB::raw('INFORMATION_SCHEMA.TABLES'))->
            where($aWhere)->
            orderBy('TABLE_NAME')->get()->toArray();
        if (is_array($data) && count($data)) {
            foreach ($data as $k => $v) {
                $ret[$v->TABLE_NAME] = $v->TABLE_NAME;
            }
        }
        return $ret;
    }
}
