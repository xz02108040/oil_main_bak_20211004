<?php

namespace App\Http\Controllers;


use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\b_menu_group;
use App\Model\Emp\b_cust_e;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class UserController extends Controller
{
    use BcustTrait,EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    | 會員:僅顯示個人資訊/登入紀錄
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
        $this->hrefMain         = 'myinfo';
        $this->langText         = 'sys_base';

        $this->routerPost       = 'myinfo';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10705');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10705');//標題列表

        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
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
        //電子簽比例
        $sign_max_height = sys_param::getParam('USER_SIGN_HEIGHT',320);
        $sign_max_width  = sys_param::getParam('USER_SIGN_WIDTH',80);
        //參數
        $js             = '';
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $action = 2;
        $id             = $this->b_cust_id;
        $bc_type        = $this->bc_type;
        $BCTYPE         = SHCSLib::getCode('BC_TYPE');
        if($bc_type == 2)
        {
            $bc_type_name = Session::get('user.be_dept','*').'-'.Session::get('user.be_title','*');
        } else {
            $bc_type_name = isset($BCTYPE[$bc_type])? $BCTYPE[$bc_type] : Lang::get('sys_base.base_10011');
        }
        //view元件參數
        $tbTitle   = $this->pageTitleList;//列表標題
        $hrefMain  = $this->hrefMain;//
        //-------------------------------------------//
        // POST
        //-------------------------------------------//
        if ($request->isMethod('post'))
        {
            //dd($request->all());
            $msg = Lang::get($this->langText.'.base_10928');

            //確認密碼規則
            $password = $request->password;
            if($password == '******' || $password == '123456') $password = '';
            if($password)
            {
                if(strlen($password) < 4)
                {
                    return \Redirect::back()
                        ->withErrors(Lang::get($this->langText.'.base_10112'))
                        ->withInput();
                } else {
                    $upAry = [];
                    $upAry['password']      = $password;
                    $ret = $this->setBcust($id,$upAry,$this->b_cust_id);
                    if($ret)
                    {
                        //動作紀錄
                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                        $msg = Lang::get($this->langText.'.base_10104');
                    }
                }
            }

            //更新自己的代理人
            if($request->agreeEmp)
            {
                $upAry = [];
                $upAry['attorney_id'] = $request->attorney_id;
                $ret = $this->setEmp($id,$upAry,$id);
                if($ret)
                {
                    Session::put('user.bcuste.attorney_id',$request->attorney_id);
                    //動作紀錄
                    LogLib::putLogAction($id,$menu,$ip,$action,'b_cust_e',$id);
                    $msg = Lang::get($this->langText.'.base_10104');
                }
            }
            //上傳簽名檔
            if($request->hasFile('signimg'))
            {
                $ImgFile    = $request->signimg;
                $extension  = $ImgFile->extension();
                $filesize  = $ImgFile->getSize();
                //[錯誤]格式錯誤
                if(!in_array(strtoupper($extension),['JPEG','JPG','PNG','GIF'])){
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10119'))
                        ->withInput();
                } elseif($filesize > $this->fileSizeLimit1) {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                        ->withInput();
                } else {
                    //圖片位置
                    $filepath = config('mycfg.user_head_path').date('Y/').$id.'/';
                    $filename = $id.'_sign.'.$extension;
                    $imagedata = file_get_contents($ImgFile);

                    //轉換 圖片大小
                    if(SHCSLib::tranImgSize($filepath.$filename,$imagedata,$sign_max_width,$sign_max_height,2))
                    {
                        $UPD = [];
                        $UPD['sign_img'] = $filepath.$filename;
                        if($this->setBcust($id,$UPD,$id))
                        {
                            $msg = Lang::get($this->langText.'.base_10927');
                            //更新Session
                            Session::put('user.sign_img',storage_path('app'.$filepath.$filename));
                            Session::put('user.sign_url',url('/img/Sign/'.SHCSLib::encode($id)));
                        }
                    } else {
                        $msg = Lang::get($this->langText.'.base_10929');
                    }
                }
            }
            //簽名板
            if(strlen($request->sign64) > 10)
            {
                //圖片位置
                $filepath = config('mycfg.user_head_path').date('Y/').$id.'/';
                $filename = $id.'_sign.jpg';
                $imagedata = base64_decode($request->sign64);

                //轉換 圖片大小
                if(SHCSLib::tranImgSize($filepath.$filename,$imagedata,$sign_max_width,$sign_max_height,2))
                {
                    $UPD = [];
                    $UPD['sign_img'] = $filepath.$filename;
                    if($this->setBcust($id,$UPD,$id))
                    {
                        $msg = Lang::get($this->langText.'.base_10927');
                        //更新Session
                        Session::put('user.sign_img',storage_path('app'.$filepath.$filename));
                        Session::put('user.sign_url',url('/img/Sign/'.SHCSLib::encode($id)));
                    }
                } else {
                    $msg = Lang::get($this->langText.'.base_10929');
                }
            }

            Session::flash('message',$msg);
            $bachHref = ($request->herfBack)? $request->herfBack : $this->hrefMain;
            return \Redirect::to($bachHref);
        }
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = LogLib::getLoginLog(Auth::user()->account,20);
        //電子簽圖檔
        $sign_img = Auth::user()->sign_img;
        $sign_url = ($sign_img)? url('/img/Sign/'.SHCSLib::encode($id)) : '';
        //-------------------------------------------//
        //  View -> 個人資訊
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //名稱
        $html = Auth::user()->name;
        $form->add('nameT1', $html,Lang::get($this->langText.'.base_10707'));
        //帳號
        $html = Auth::user()->account;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10708'));
        //密碼
        $html  = $form->pwd('password',4);
        $html .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY');
        $html .= $form->hidden('id',$id);
        $form->add('nameT3', $html,Lang::get($this->langText.'.base_10709'));
        //身分
        $html = $bc_type_name;
        $form->add('nameT3', $html,Lang::get($this->langText.'.base_10718'));
        //權限群組
        $html = b_menu_group::getName(Auth::user()->b_menu_group_id);
        $form->add('nameT4', $html,Lang::get($this->langText.'.base_10710'));
        //職員的代理人
        if($bc_type == 2)
        {
            $be_dept_id     = Session::get('user.bcuste.be_dept_id',0);
            $deptEmpAry     = b_cust_e::getSelect(0,$be_dept_id,0,$id);
            $attorney_id    = Session::get('user.bcuste.attorney_id',0);

            $html  = $form->select('attorney_id',$deptEmpAry,$attorney_id,4);
            $html .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeEmp');
            $form->add('nameT4', $html,Lang::get('sys_emp.emp_12'));
        }
        //電子簽 2019-08-05
        $html  = ($sign_url)? Html::image($sign_url,'',['class'=>'img-responsive','height'=>'30%']) : '';
        $html .= '<div class="btn-group btn-group-justified">';
        $html .= $form->linkbtn('#',Lang::get('sys_btn.btn_67'),1,'showSign1','','showSign(1)');
        $html .= $form->linkbtn('#',Lang::get('sys_btn.btn_68'),1,'showSign2','','showSign(2)');
        $html .= '</div><div id="showSign1div" style="display: none;">';
        $html .= $form->file('signimg',Lang::get($this->langText.'.base_10930',['width'=>$sign_max_width,'height'=>$sign_max_height]));
        $html .= '<div id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="'.$sign_max_width.'" height="'.$sign_max_height.'" /></div>';
        $html .= $form->submit(Lang::get('sys_btn.btn_18'),'1','agreeY');
        $html .= '</div>';
        $html .= '<div id="showSign2div" style="display: none;"><div class="row-canvas">
        <canvas id="ppCanvas" width="640" height="180"></canvas>
        <div class="shutdown"></div>
      </div>
      <div class="row">
        <div class="col-md-12">

          <!-- Table 1 -->
          <table width="100%" class="functions">
            <tbody>
              <!-- Cols 1 -->
              <tr>
                <td width="25%">
                  <button type="button" id="initBtn" class="btn btn-block btn-success btn-lg" onclick="initDevice()">連接手寫板</button>
                </td>
                <td width="25%">
                  <button type="button" id="uninitBtn" class="btn btn-block btn-success btn-lg init" onclick="uninitDevice()" disabled>關閉手寫板</button>
                </td>
                <td width="25%">
                  <button type="button" class="btn btn-block btn-success btn-lg init" onclick="clearInk()" disabled>清除畫面</button>
                </td>
                <td width="25%">
                    <input type="hidden" id="encodeType" name="encodeType" value="2">
				    <input type="hidden" id="sign64" name="sign64" value="">
                    <button type="button" class="btn btn-block btn-danger btn-lg init" onclick="signencode()" disabled>更新</button>
                </td>
              </tr>
              <tr>
                <td>
                  手寫板型號:
                </td>
                <td>
                  <input type="text" value="L398" readonly="readonly" class="form-control"></input>
                </td>
                <td>
                  <button type="button" class="btn btn-block btn-success btn-lg init" onclick="getAbout()" disabled>版本</button>
                </td>
              </tr> 
             
            </tbody>
          </table>

          <!-- Table 2 -->
          <Table class="encoding" align="center">
            <tr>
              <td>
				
              </td>
            </tr>

          </table>

        </div>
      </div>
        
        

      <div class="modal fade" id="playbackModal" tabindex="-1" role="dialog" aria-labelledby="playbackLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title" id="playbackLabel">Playback Drawing Video</h4>
            </div>
            <div class="modal-body">
                <video src="" id="playback-video" autoplay="false" controls style="width: 100%;">

                </video>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      </div>
      ';
        $form->add('nameT98',$html,Lang::get($this->langText.'.base_10712'));
        //最後異動人員 ＋ 時間
        $html = Lang::get($this->langText.'.base_10614',['name'=>User::getName(Auth::user()->mod_user),'time'=>Auth::user()->updated_at]);
        $form->add('nameT98',$html,Lang::get($this->langText.'.base_10613'));


        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10713')]; //日期
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10714')]; //ＩＰ
        $heads[] = ['title'=>Lang::get($this->langText.'.base_10715')]; //狀態

        $table->addHead($heads,0);
        if($listAry->count())
        {
            foreach($listAry as $value)
            {
                $name1      = substr($value->created_at,0,16);
                $name2      = $value->ip;
                $name3      = ($value->is_suc == 'Y')? '.base_10716' : '.base_10717' ;
                $name3      = Lang::get($this->langText.$name3) ;
                $color3     = ($value->is_suc == 'Y')? 2 : 4 ;

                $tBody[] = [
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3,'label'=>$color3],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->addHr();
        $form->addRow($table->output());
        unset($table);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
           
        
            $("input[name=\'signimg\']").change(function() {
              readURL(this);
              $("#blah_div").hide();
            });
            function readURL(input) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $("#blah").attr("src", e.target.result);
                  $("#blah_div").show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
            
        });
        function showSign(showNo)
            {
                if(showNo == 1)
                {
                    $("#showSign1div").show();
                    $("#showSign2div").hide();
                } else {
                    $("#showSign2div").show();
                    $("#showSign1div").hide();
                }
            }
        ';

        //-------------------------------------------//
        //  View -> CSS
        //-------------------------------------------//
        $css = '
            .row-canvas {
              text-align: center;
              margin: 1em 0;
              width: 100%;
              position: relative;
            }
            .shutdown {
              width: 640px;
              height: 180px;
              position: absolute;
              z-index: 2;
              background: #333;
              top: 1px;
              left: 50%;
              margin-left: -300px;
              display: none;
            }
            #ppCanvas {
              border: thin solid #ccc;
              box-shadow: 4px 4px 12px -2px rgba(51, 51, 51, 0.5);
              z-index: 1;
            }
            
            .functions td {
              padding: 5px;
              width: 16.65%;
            }
            
            .functions2 td {
              padding: 5px;
              width: 20%;
            }
            
            .functions2 .modal-content {
              padding: 15px;
              text-align: center;
            }
            
            .functions2 .modal-content p {
              font-size: 1.5em;
            }
            
            .encoding {
              margin-top: 1.5em;
            }
            
            .encoding td {
              padding: 5px;
            }
            
            .decode {
              margin-top: 10px;
            }
            
            .decode td {
              margin: 10px 0;
            }
            
            hr.style-three {
              border: 0;
              border-bottom: 1px dashed #ccc;
              background: #999;
            }

        ';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>Auth::user()->name,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js,'css'=>$css];
        return view('sign',$retArray);
    }
}
