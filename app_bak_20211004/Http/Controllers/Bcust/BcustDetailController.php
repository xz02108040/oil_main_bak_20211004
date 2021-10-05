<?php

namespace App\Http\Controllers\Bcust;


use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\bc_type_app;
use App\Model\sys_code;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_user;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;
use Storage;

class BcustDetailController extends Controller
{
    use BcustTrait,EmpTrait,BcustATrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | BcustDetailController
    |--------------------------------------------------------------------------
    |
    | 個人資料 維護
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct(Request $request)
    {
        //身分驗證
        $this->middleware('auth');
        //讀取選限
        $this->uri              = SHCSLib::getUri($request->route()->uri);
        $this->isWirte          = 'N';
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = '/';
        $this->hrefEmp          = 'emp';
        $this->hrefSupply       = 'contractormember';
        $this->langText         = 'sys_base';

        $this->hrefMainDetail   = 'person/';
        $this->hrefMainNew      = 'new_person';
        $this->routerPost       = 'postPerson';

        $this->pageTitleMain    = Lang::get($this->langText.'.base_10900');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.base_10900');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.base_10900');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.base_10900');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
    }

    /**
     * 單筆資料 編輯
     */
    public function index(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        $isRoot = ($this->isRootDept || $this->isRoot)? 1 : 0;
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $isEmp          = ($request->isEmp == 'Y')? 1 : 0;
        $isSupply       = ($request->has('pid'))? SHCSLib::decode($request->pid) : 0;
        $allowAry       = SHCSLib::getCode('ALLOW');
        $sexAry         = SHCSLib::getCode('SEX');
        $bloodAry       = SHCSLib::getCode('BLOOD',1);
        $bloodRhAry     = SHCSLib::getCode('bloodRh');
        $kindAry        = SHCSLib::getCode('PERSON_KIND',1);
        $bctypeAry      = SHCSLib::getCode('BC_TYPE');
        $nationAry      = SHCSLib::getCode('NATION_TYPE');
        //view元件參數
        $hrefBack       = ($isEmp)? $this->hrefEmp : (($isSupply)? $this->hrefSupply.'?pid='.$request->pid : $this->hrefHome);
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);

        //如果沒有資料
        if(!isset($getData->b_cust_id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $name       = $getData->name;
            $isIN       = $getData->isIN;
            $bc_type    = isset($bctypeAry[$getData->bc_type]) ? $bctypeAry[$getData->bc_type] : '';
            $nationName = isset($nationAry[$getData->nation]) ? $nationAry[$getData->nation] : '';
            //$bc_type_app= bc_type_app::getName($getData->bc_type_app);

            $A1         = $getData->sex; //
            $A2         = ($isRoot)? $getData->bc_id : SHCSLib::genBCID($getData->bc_id); //
            $A3         = ($getData->birth != '1970-01-01')? $getData->birth : ''; //
            $A4         = $getData->head_img ? url('img/User/'.$urlid)  : ''; //
            $A5         = $getData->blood; //
            $A6         = $getData->bloodRh; //
            $A7         = $getData->tel1; //
            $A8         = $getData->mobile1; //
            $A9         = $getData->email1; //
            $A10        = $getData->addr1; //
            $A11        = $getData->kin_user; //
            $A12        = $getData->kin_kind; //
            $A13        = $getData->kin_tel; //
            $A14        = $getData->bc_id; //
            $A15        = $getData->nation; //
            $A98        = Lang::get($this->langText.'.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]); //

            //承攬商
            $C1         = 4;//($isSupply)? 5 : 4;
            $C2         = 0;//($isSupply)? 1 : 0;

        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//

        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);

        //--- 帳號區 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.base_10901'),3);
        $form->addHtml( $html );

        //table
        $table = new TableLib();

        //姓名＋圖片
        $fhtml1 = ($this->isWirte == 'Y')? $form->text('name',$name) : $name;
        $fhtml2 = ($A4)? Html::image($A4,'',['class'=>'img-responsive','height'=>'30%']) : '';
        $fhtml2.= ($this->isWirte == 'Y')? $form->file('headImg') : '';
        $fhtml2 .= '<span id="blah_div" style="display: none;"><img id="blah" src="#" alt="" width="240" /></span>';
        $tBody[] = [
            '0'=>['name'=>Lang::get($this->langText.'.base_10707'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
            '3'=>['name'=> $fhtml2,'style'=>'width:35%;','row'=>$C1,'col'=>2],
        ];
        unset($fhtml1,$fhtml2);

        //會員編號
        $fhtml1 = $id;
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.base_10706'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        //帳號身分
        $fhtml1 = $bc_type;
        //客戶名稱 / 會員編號
        $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.base_10718'),'b'=>1,'style'=>'width:15%;'],//$no
            '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
        ];

        //針對承攬商成員 可控管進出
//        if($isSupply)
//        {
//            //ＡＰＰ身分
//            $fhtml1 = $form->select('isIN',$allowAry,$isIN);
//            //客戶名稱 / 會員編號
//            $tBody[] = ['0'=>['name'=>Lang::get($this->langText.'.base_10721'),'b'=>1,'style'=>'width:15%;'],//$no
//                '1'=>['name'=> $fhtml1,'style'=>'width:35%;'],
//            ];
//        }
        $table->addBody($tBody);
        $form->addHtml( $table->output() );

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 個人資訊 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.base_10902'),4);
        $form->addHtml( $html );
        //性別
        $html = ($this->isWirte == 'Y')? $form->select('sex',$sexAry,$A1) : (isset($sexAry[$A1])? $sexAry[$A1] : '');
        $form->add('nameT6', $html,Lang::get($this->langText.'.base_10905'),1);
        //國籍
        $html = ($this->isWirte == 'Y')? $form->select('nation',$nationAry,$A15) : $nationName;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10904'),$C2);
        //身分證
        $html = ($this->isWirte == 'Y')? $A2.$form->text('bc_id1','',2) : $A2;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10906'),$C2);
        //生日
        $html = ($this->isWirte == 'Y')? $form->date('birth',$A3) : $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.base_10907'));
        //血型
        $html = ($this->isWirte == 'Y')? $form->select('blood',$bloodAry,$A5): $A5;
        $form->add('nameT5', $html,Lang::get($this->langText.'.base_10908'),$C2);
        //血型ＲＨ
        $html = ($this->isWirte == 'Y')? $form->select('bloodRh',$bloodRhAry,$A6): $A6;
        $form->add('nameT6', $html,Lang::get($this->langText.'.base_10909'));
        //電話
        $html = ($this->isWirte == 'Y')? $form->text('tel1',$A7): $A7;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10910'));
        //行動電話
        $html = ($this->isWirte == 'Y')? $form->text('mobile1',$A8): $A8;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10911'),$C2);
        //Email
        $html = ($this->isWirte == 'Y')? $form->text('email1',$A9): $A9;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10912'));
        //地址
        //2020-11-10 地址改為必填
        $isRequired = ($isSupply)? 1 : 0;
        $html = ($this->isWirte == 'Y')? $form->text('addr1',$A10,8): $A10;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10913'),$isRequired);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //--- 緊急聯絡人 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.base_10903'),5);
        $form->addHtml( $html );
        //緊急聯絡人
        $html = ($this->isWirte == 'Y')? $form->text('kind_user',$A11) : $A11;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10914'),$C2);
        //關係
        $html = ($this->isWirte == 'Y')? $form->select('kind_type',$kindAry,$A12) : ($A12 > 0 && isset($kindAry[$A12])? $kindAry[$A12] : '');
        $form->add('nameT5', $html,Lang::get($this->langText.'.base_10915'),$C2);
        //聯絡電話
        $html = ($this->isWirte == 'Y')? $form->text('kind_tel',$A13) : $A13;
        $form->add('nameT2', $html,Lang::get($this->langText.'.base_10916'),$C2);
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);
        //停用
        if(($this->isWirte == 'Y'))
        {
            //$html = $form->checkbox('isClose','Y');
            //$form->add('isCloseT',$html,Lang::get($this->langText.'.base_10926'));
        }

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get($this->langText.'.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('bc_id',$A14);
        $submitDiv.= $form->hidden('isSupply',$isSupply);
        $submitDiv.= $form->hidden('herfBack',$hrefBack);
        $form->boxFoot($submitDiv);

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
            $("#birth").datepicker({
                format: "yyyy-mm-dd",
                changeYear: true, 
                language: "zh-TW"
            });
            $("input[name=\'headImg\']").change(function() {
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
        });';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //年紀參數
        $birth_min_param = sys_param::getParam('BIRTH_DATE_LIMIT1',18);
        $birth_max_param = sys_param::getParam('BIRTH_DATE_LIMIT2',65);

        //資料不齊全

        if($request->isSupply)
        {
            if( !$request->has('agreeY') || !$request->id )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10103'))
                    ->withInput();
            }
            if( !$request->name)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10172',['name'=>Lang::get($this->langText.'.base_10707')]))
                    ->withInput();
            }
            if( !$request->nation)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10172',['name'=>Lang::get($this->langText.'.base_10904')]))
                    ->withInput();
            }
            if(!$request->bc_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10172',['name'=>Lang::get($this->langText.'.base_10906')]))
                    ->withInput();
            }
            if( !$request->blood )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10172',['name'=>Lang::get($this->langText.'.base_10908')]))
                    ->withInput();
            }
            if( !$request->mobile1 )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10172',['name'=>Lang::get($this->langText.'.base_10911')]))
                    ->withInput();
            }
            elseif(!$request->kind_user || !$request->kind_type || !$request->kind_tel)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1001'))
                    ->withInput();
            }
            elseif(!$request->addr1)
            {
                //2020-11-10 地址改為必填
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_supply.supply_1032'))
                    ->withInput();
            }
        } else {
            if( !$request->has('agreeY') || !$request->id || !$request->name )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.base_10103'))
                    ->withInput();
            }
        }
        //身分證格式錯誤
        if($request->nation == 1 && $request->bc_id1 && !CheckLib::isBcID($request->bc_id1))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10920'))
                ->withInput();
        }
        //Email格式錯誤
        elseif($request->email1 && !CheckLib::isMail($request->email1))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10921'))
                ->withInput();
        }
        //生日限制
        elseif($request->birth && $request->birth != '1970-01-01' && SHCSLib::birthday($request->birth) > $birth_max_param)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10923',['name'=>$birth_max_param]))
                ->withInput();
        }
        //生日限制
        elseif($request->birth && $request->birth != '1970-01-01' && SHCSLib::birthday($request->birth) < $birth_min_param)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10924',['name'=>$birth_min_param]))
                ->withInput();
        }
        //緊急聯絡人資訊不足
        elseif($request->kind_user && (!$request->kind_type || !$request->kind_tel))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.base_10922'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;

            //身分證格式重複
            if($request->bc_id && CheckLib::checkBCIDExist($request->bc_id,$id))
                {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10925'))
                    ->withInput();
            }
        }
        $isNew  = 0;
        $action = ($isNew)? 1 : 2;
        $filepath = $filename = '';

        //處理圖片
        if($request->hasFile('headImg'))
        {
            //人頭像比例
            $head_max_height = sys_param::getParam('USER_HEAD_HEIGHT',640);
            $head_max_width  = sys_param::getParam('USER_HEAD_WIDTH',360);
            $ImgFile    = $request->headImg;
            $exif       = @exif_read_data ($ImgFile,0,true); //圖片ＥＸＩＦ數值可能出現異常
            $extension  = $ImgFile->extension();
            $filesize   = $ImgFile->getSize();
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
                //調查是否有無轉移角度
                $Orientation = (isset($exif['IFD0']) && isset($exif['IFD0']['Orientation']))? $exif['IFD0']['Orientation'] : 0;
                //圖片位置
                $filepath = config('mycfg.user_head_path').date('Y/').$id.'/';
                $filename = $id.'_head.'.$extension;
                $imagedata = file_get_contents($ImgFile);

                //轉換 圖片大小
                if(!SHCSLib::tranImgSize($filepath.$filename,$imagedata,$head_max_width,$head_max_height,1,$Orientation))
                {
                    $filepath = $filename = '';
                }
            }
        }

        $upAry = $upAry2 = array();
        if(!$isNew)
        {
            $upAry['b_cust_id']     = $id;
        }
        $upAry['head_img']          = $filepath.$filename;
        $upAry['sex']               = $request->sex;
        $upAry['bc_id']             = ($request->bc_id1 && $request->bc_id1 != $request->bc_id)? $request->bc_id1 :$request->bc_id;
        $upAry['nation']            = $request->nation;
        $upAry['birth']             = $request->birth;
        $upAry['blood']             = $request->blood;
        $upAry['bloodRh']           = $request->bloodRh;
        $upAry['tel1']              = $request->tel1;
        $upAry['mobile1']           = $request->mobile1;
        $upAry['email1']            = $request->email1;
        $upAry['addr1']             = $request->addr1;
        $upAry['kin_user']          = $request->kind_user;
        $upAry['kin_kind']          = $request->kind_type;
        $upAry['kin_tel']           = $request->kind_tel;

        $upAry2['name']             = $request->name;
        $upAry2['isIN']             = (isset($request->isIN) && $request->isIN == 'N')? 'N' : 'Y';
        $upAry2['isClose']          = ($request->isClose == 'Y')? 'Y' : 'N';
//dd($upAry);
        //新增
        if($isNew)
        {

        } else {
            //修改
            $ret1 = $this->setBcust($id,$upAry2,$this->b_cust_id);
            $ret2 = $this->setBcustA($id,$upAry,$this->b_cust_id);
            if($ret1 || $ret2)
            {
                $ret = ($ret1 > 0 || $ret2 > 0)? $id : -1;
            }
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get($this->langText.'.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                if($ret1 > 0)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                }
                if($ret2 > 0)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_a',$id);
                }

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get($this->langText.'.base_10104'));
                return \Redirect::to($request->herfBack);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get($this->langText.'.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function getData($uid = 0)
    {
        $this->getBcustParam();
        $ret  = view_user::find($uid);
        if(!isset($ret->b_cust_id))
        {
            //如果存在 帳號，則自動新增 個人資訊一筆
            if(User::isExist($uid))
            {
                if($id = $this->createBcustA(['b_cust_id'=>$uid],$this->b_cust_id))
                {
                    $ret  = view_user::find($id);
                }
            }
        }

        return $ret;
    }

}
