<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//session
Route::get('sess', 'SessController@index')->name('sess');
//測試用
Route::get('testapi', 'TestController@index')->name('testApi');
Route::get('testshow', 'TestReportController@index')->name('testShow');
Route::get('testpage', 'TestReportController@page')->name('testpage');
Route::any('testsql',  'TestSQLController@index')->name('testsql');
Route::any('printpermit2019', 'TestPermitController@index')->name('testPermitMain');
//登入
Route::get('login', 'Auth\LoginController@showLogin')->name('loginShow');
Route::post('login', 'Auth\LoginController@login')->name('login');
//登出
Route::any('logout', 'Auth\LoginController@logout')->name('logout');

//================================== APP專用 隱私權 =========================================//
Route::any('Privacy', 'PrivacyController@index')->name('Privacy');

//==================================API=========================================//
//門禁API
Route::any('httcapi',   ['as' => 'httcapi',    'uses' => 'AppApiController@index']);
//門禁API
Route::any('doorapi',   ['as' => 'doorapi',    'uses' => 'DoorApiController@index']);
//配卡API
Route::any('paircardapi',   ['as' => 'paircardapi','uses' => 'CardPairApiController@index']);
//報表API
Route::any('reptapi',   ['as' => 'reptapi','uses' => 'ReportApiController@index']);
//Tool API
Route::any('toolapi',   ['as' => 'httcapi','uses' => 'ToolApiController@index']);

//==================================系統基本=========================================//
//User
Route::any('myinfo',     ['as' => 'myinfo',   'uses' => 'UserController@index']);
//帳號管理
Route::any('user',       ['as' => 'custList',    'uses' => 'BcustController@index']);
Route::get('user/{id}',  ['as' => 'bcustEdit',   'uses' => 'BcustController@show']);
Route::get('new_user',   ['as' => 'bcustCreate', 'uses' => 'BcustController@create']);
Route::post('bcust/{id}',['as' => 'postBcust',   'uses' => 'BcustController@post']);
Route::any('genBcustPwd',['as' => 'genBcustPwd',   'uses' => 'BcustController@genBcustPwd']);
//[總部]MENU
Route::any('menu',      ['as' => 'menuList',   'uses' => 'MenuController@index']);
Route::get('menu/{id}', ['as' => 'menuEdit',   'uses' => 'MenuController@show']);
Route::get('new_menu',  ['as' => 'menuCreate', 'uses' => 'MenuController@create']);
Route::post('menu/{id}',['as' => 'postMenu',   'uses' => 'MenuController@post']);
//[總部]選單群組
Route::any('menugroup',      ['as' => 'begroupList',   'uses' => 'MenuGroupController@index']);
Route::get('menugroup/{id}', ['as' => 'begroupEdit',   'uses' => 'MenuGroupController@show']);
Route::get('new_menugroup',  ['as' => 'begroupCreate', 'uses' => 'MenuGroupController@create']);
Route::post('menugroup/{id}',['as' => 'postBegroup',   'uses' => 'MenuGroupController@post']);
//[總部]選單群組權限設定
Route::get('menuauth/{id}', ['as' => 'menuauthList',   'uses' => 'MenuAuthController@index']);
Route::post('menuauth/{id}',['as' => 'postMenuAuth',   'uses' => 'MenuAuthController@post']);
//[承攬商]帳號管理
Route::get('userc',       ['as' => 'custcList',    'uses' => 'BcustContractorController@index']);
Route::get('userc/{id}',  ['as' => 'bcustcEdit',   'uses' => 'BcustContractorController@show']);
//Route::get('new_user',   ['as' => 'bcustCreate', 'uses' => 'BcustContractorController@create']);
Route::post('bcustc/{id}',['as' => 'postBcustC',   'uses' => 'BcustContractorController@post']);
//[承攬商]MENU
Route::any('menuc',      ['as' => 'menucList',   'uses' => 'MenuContractorController@index']);
Route::get('menuc/{id}', ['as' => 'menucEdit',   'uses' => 'MenuContractorController@show']);
Route::get('new_menuc',  ['as' => 'menucCreate', 'uses' => 'MenuContractorController@create']);
Route::post('menuc/{id}',['as' => 'postMenuC',   'uses' => 'MenuContractorController@post']);
//[承攬商]選單群組
Route::any('menugroupc',      ['as' => 'begroupcList',   'uses' => 'MenuContractorGroupController@index']);
Route::get('menugroupc/{id}', ['as' => 'begroupcEdit',   'uses' => 'MenuContractorGroupController@show']);
Route::get('new_menugroupc',  ['as' => 'begroupcCreate', 'uses' => 'MenuContractorGroupController@create']);
Route::post('menugroupc/{id}',['as' => 'postBegroupC',   'uses' => 'MenuContractorGroupController@post']);
//[承攬商]選單群組權限設定
Route::get('menuauthc/{id}', ['as' => 'menuauthcList',   'uses' => 'MenuContractorAuthController@index']);
Route::post('menuauthc/{id}',['as' => 'postMenuAuthC',   'uses' => 'MenuContractorAuthController@post']);

//SysCode
Route::get('syscode',      ['as' => 'syscodeList',   'uses' => 'SysCodeController@index']);
Route::get('syscode/{id}', ['as' => 'syscodeEdit',   'uses' => 'SysCodeController@show']);
Route::get('new_syscode',  ['as' => 'syscodeCreate', 'uses' => 'SysCodeController@create']);
Route::post('syscode/{id}',['as' => 'postSyscode',   'uses' => 'SysCodeController@post']);
//SysParam
Route::get('sysparam',      ['as' => 'sysparamList',   'uses' => 'SysParamController@index']);
Route::get('sysparam/{id}', ['as' => 'sysparamEdit',   'uses' => 'SysParamController@show']);
Route::get('new_sysparam',  ['as' => 'sysparamCreate', 'uses' => 'SysParamController@create']);
Route::post('sysparam/{id}',['as' => 'postSysparam',   'uses' => 'SysParamController@post']);

//==================================圖片=========================================//
//個人資訊
Route::get('img/User/{id}',     ['as' => 'personImg',   'uses' => 'ImgController@showUserHeadImg']);
//個人電子簽名
Route::get('img/Sign/{id}',     ['as' => 'personSignImg',   'uses' => 'ImgController@showUserSignImg']);
//個人頭像
Route::get('img/RPMember/{id}', ['as' => 'rpmemberImg', 'uses' => 'ImgController@showUserRPHeadImg']);
//個人_工程身份/申請
Route::get('img/License/{id}', ['as' => 'licenseImg', 'uses' => 'ImgController@showLicenseImg']);
Route::get('img/RpLicense/{id}', ['as' => 'licenseImg', 'uses' => 'ImgController@showRpLicenseImg']);
//門禁照片
Route::get('img/Door/{id}',     ['as' => 'doorImg',     'uses' => 'ImgController@showDoorImg']);
//車輛照片
Route::get('img/Car/{id}',      ['as' => 'carImg',      'uses' => 'ImgController@showCarImg']);
Route::get('img/RpCar/{id}',    ['as' => 'carRpImg',    'uses' => 'ImgController@showRpCarImg']);
//工作許可證照片
Route::get('img/Permit/{id}',   ['as' => 'permitImg',   'uses' => 'ImgController@showPermitImg']);
//工作許可證照片2
Route::get('img/Permit2/{id}',  ['as' => 'permitImg',   'uses' => 'ImgController@showPermitImg2']);

//==================================後門=========================================//
//教育訓練白名單
Route::get('coursewhiteorder',     ['as' => 'coursewhiteorderList',    'uses' => 'Engineering\CourseWhiteOrderController@index']);
Route::any('gencoursewhiteorder',  ['as' => 'coursewhiteorderCreate',  'uses' => 'Engineering\CourseWhiteOrderController@create']);
//測試推播
Route::any('pushtest',  ['as' => 'pusthtest',  'uses' => 'Tmp\PushTestController@index']);
//門禁白名單
Route::get('doorwhiteorder',     ['as' => 'doorwhiteorderList',    'uses' => 'Report\DoorMenWhiteOrderController@index']);
Route::get('carwhiteorder',      ['as' => 'carwhiteorderList',    'uses' => 'Report\DoorCarWhiteOrderController@index']);

//==================================檔案=========================================//
//下載課程用檔案
Route::get('file/{id}',   ['as' => 'courseFile',    'uses' => 'FileController@downFile']);

//==================================帳號=========================================//
//個人資訊
Route::get('person/{id}',   ['as' => 'personList',    'uses' => 'Bcust\BcustDetailController@index']);
Route::post('person/{id}',  ['as' => 'postPerson',   'uses' => 'Bcust\BcustDetailController@post']);
//BcTypeApp
Route::get('bctypeapp',      ['as' => 'bctypeappList',    'uses' => 'Bcust\BcTypeAppController@index']);
Route::get('bctypeapp/{id}', ['as' => 'bctypeappEdit',    'uses' => 'Bcust\BcTypeAppController@show']);
Route::get('new_bctypeapp',  ['as' => 'bctypeappeCreate', 'uses' => 'Bcust\BcTypeAppController@create']);
Route::post('bctypeapp/{id}',['as' => 'postBctypeapp',    'uses' => 'Bcust\BcTypeAppController@post']);

Route::get('findBcType',      ['as' => 'findBcType',   'uses' => 'Bcust\FindBcTypeController@FindEmp']);

//==================================職員組織=========================================//
//Find
Route::get('findEmp',      ['as' => 'findEmp',   'uses' => 'Emp\FindEmpController@FindEmp']);

//Emp
Route::any('emp',      ['as' => 'empList',   'uses' => 'Emp\EmpController@index']);
Route::get('emp/{id}', ['as' => 'empEdit',   'uses' => 'Emp\EmpController@show']);
Route::get('new_emp',  ['as' => 'empCreate', 'uses' => 'Emp\EmpController@create']);
Route::post('emp/{id}',['as' => 'postEmp',   'uses' => 'Emp\EmpController@post']);
//Excel匯入
Route::get( 'exceltoemp',['as' => 'ImportToEmpIndex',   'uses' => 'Emp\ImportToEmpController@index']);
Route::post('exceltoemp',['as' => 'ImportToEmp',        'uses' => 'Emp\ImportToEmpController@post']);
Route::get('exceltoemp_err',['as' => 'ImportToEmpDown', 'uses' => 'Emp\ImportToEmpController@download']);

//職稱
Route::get('emptitle',      ['as' => 'empTitleList',   'uses' => 'Emp\EmpTitleController@index']);
Route::get('emptitle/{id}', ['as' => 'empTitleEdit',   'uses' => 'Emp\EmpTitleController@show']);
Route::get('new_emptitle',  ['as' => 'empTitleCreate', 'uses' => 'Emp\EmpTitleController@create']);
Route::post('emptitle/{id}',['as' => 'postEmpTitle',   'uses' => 'Emp\EmpTitleController@post']);

//部門
Route::get('empdept',      ['as' => 'empDeptList',   'uses' => 'Emp\EmpDeptController@index']);
Route::get('empdept/{id}', ['as' => 'empDeptEdit',   'uses' => 'Emp\EmpDeptController@show']);
Route::get('new_empdept',  ['as' => 'empDeptCreate', 'uses' => 'Emp\EmpDeptController@create']);
Route::post('empdept/{id}',['as' => 'postEmpDept',   'uses' => 'Emp\EmpDeptController@post']);

//部門 vs 職稱
Route::get('empdepttitle',      ['as' => 'empDeptTitleList',   'uses' => 'Emp\EmpDeptTitleController@index']);
Route::get('empdepttitle/{id}', ['as' => 'empDeptTitleEdit',   'uses' => 'Emp\EmpDeptTitleController@show']);
Route::get('new_empdepttitle',  ['as' => 'empDeptTitleCreate', 'uses' => 'Emp\EmpDeptTitleController@create']);
Route::post('empdepttitle/{id}',['as' => 'postEmpDeptTitle',   'uses' => 'Emp\EmpDeptTitleController@post']);

//====================================承攬商=======================================//
//Find
Route::get('findContractor',      ['as' => 'findContractor',   'uses' => 'Supply\FindSupplyController@findSupply']);
Route::get('findIdentity',        ['as' => 'findIdentity',     'uses' => 'Supply\FindSupplyController@findSupplyIdentity']);
Route::get('findSupplyRpAproc',   ['as' => 'findSupplyRpAproc','uses' => 'Supply\FindSupplyController@findSupplyRpAproc']);
//承攬商
Route::get('contractor',      ['as' => 'contractorList',   'uses' => 'Supply\SupplyController@index']);
Route::get('contractor/{id}', ['as' => 'contractorEdit',   'uses' => 'Supply\SupplyController@show']);
Route::get('new_contractor',  ['as' => 'contractorCreate', 'uses' => 'Supply\SupplyController@create']);
Route::post('contractor/{id}',['as' => 'postContractor',   'uses' => 'Supply\SupplyController@post']);
//承攬商成員
Route::any('contractormember',      ['as' => 'contractormemberList',   'uses' => 'Supply\SupplyMemberController@index']);
//Route::get('contractormember/{id}', ['as' => 'contractormemberEdit',   'uses' => 'Supply\SupplyMemberController@show']);
Route::get('new_contractormember/{id}',  ['as' => 'contractormemberCreate', 'uses' => 'Supply\SupplyMemberController@create']);
Route::post('contractormember/{id}',['as' => 'postContractormember',   'uses' => 'Supply\SupplyMemberController@post']);
//Excel匯入
Route::get( 'exceltocontractor',['as' => 'ImportToContractorIndex',   'uses' => 'Supply\ImportToSupplyController@index']);
Route::post('exceltocontractor',['as' => 'ImportToContractor',        'uses' => 'Supply\ImportToSupplyController@post']);
Route::get('exceltocontractor_err',['as' => 'ImportToContractorDown', 'uses' => 'Supply\ImportToSupplyController@download']);
//承攬商成員擁有的工程身分
Route::any('contractormemberidentity',           ['as' => 'contractormemberidentityList',   'uses' => 'Supply\SupplyMemberIdentityController@index']);
Route::get('contractormemberidentity/{id}',      ['as' => 'contractormemberidentityEdit',   'uses' => 'Supply\SupplyMemberIdentityController@show']);
Route::any('new_contractormemberidentity/{id}',  ['as' => 'contractormemberidentityCreate', 'uses' => 'Supply\SupplyMemberIdentityController@create']);
Route::post('contractormemberidentity/{id}',     ['as' => 'postContractormemberidentity',   'uses' => 'Supply\SupplyMemberIdentityController@post']);
//承攬商成員擁有的證照證明
Route::get('contractormemberlicense',           ['as' => 'contractormemberlicenseList',   'uses' => 'Supply\SupplyMemberLicenseController@index']);
Route::get('contractormemberlicense/{id}',      ['as' => 'contractormemberlicenseEdit',   'uses' => 'Supply\SupplyMemberLicenseController@show']);
Route::any('new_contractormemberlicense/{id}',  ['as' => 'contractormemberlicenseCreate', 'uses' => 'Supply\SupplyMemberLicenseController@create']);
Route::post('contractormemberlicense/{id}',     ['as' => 'postContractormemberlicense',   'uses' => 'Supply\SupplyMemberLicenseController@post']);
//承攬商-工程身分
Route::get('engineeringidentity',      ['as' => 'engineeringidentityList',   'uses' => 'Supply\SupplyEngineeringIdentityController@index']);
Route::get('engineeringidentity/{id}', ['as' => 'engineeringidentityEdit',   'uses' => 'Supply\SupplyEngineeringIdentityController@show']);
Route::get('new_engineeringidentity',  ['as' => 'engineeringidentityCreate', 'uses' => 'Supply\SupplyEngineeringIdentityController@create']);
Route::post('engineeringidentity/{id}',['as' => 'postEngineeringidentity',   'uses' => 'Supply\SupplyEngineeringIdentityController@post']);
//承攬商-工程身分-證照
Route::get('engineeringidentitylicense',      ['as' => 'engineeringidentitylicenseList',   'uses' => 'Supply\SupplyEngineeringIdentityLicenseController@index']);
Route::get('engineeringidentitylicense/{id}', ['as' => 'engineeringidentitylicenseEdit',   'uses' => 'Supply\SupplyEngineeringIdentityLicenseController@show']);
Route::get('new_engineeringidentitylicense',  ['as' => 'engineeringidentitylicenseCreate', 'uses' => 'Supply\SupplyEngineeringIdentityLicenseController@create']);
Route::post('engineeringidentitylicense/{id}',['as' => 'postEngineeringidentitylicense',   'uses' => 'Supply\SupplyEngineeringIdentityLicenseController@post']);

//成員申請單
Route::any('rp_contractormember',      ['as' => 'contractorrpmemberList',   'uses' => 'Supply\SupplyRPMemberController@index']);
Route::get('rp_contractormember/{id}', ['as' => 'contractorrpmemberEdit',   'uses' => 'Supply\SupplyRPMemberController@show']);
Route::get('rp_contractormember2/{id}',['as' => 'contractorrpmemberEdit2',  'uses' => 'Supply\SupplyRPMemberController@show2']);
//Route::get('new_rp_contractormember',  ['as' => 'contractorrpmemberCreate', 'uses' => 'Supply\SupplyRPMemberController@create']);
Route::post('rp_contractormember/{id}',['as' => 'postContractorrpmember',   'uses' => 'Supply\SupplyRPMemberController@post']);
Route::any('contractorrpmemberapply1/{id}',['as' => 'contractorrpmemberapplyList',   'uses' => 'Supply\SupplyRPMemberController@setIdentityLicense']);
//加入工程案件申請單
Route::any('rp_contractorproject',      ['as' => 'contractorrpprojectList',         'uses' => 'Supply\SupplyRPProjectController@index']);
Route::get('rp_contractorproject/{id}', ['as' => 'contractorrpprojectEdit',         'uses' => 'Supply\SupplyRPProjectController@show']);
Route::get('rp_contractorproject2/{id}', ['as' => 'contractorrpprojectEdit2',         'uses' => 'Supply\SupplyRPProjectController@show2']);
//Route::get('new_rp_contractormember',  ['as' => 'contractorrpprojectCreate', 'uses' => 'Supply\SupplyRPMemberController@create']);
Route::post('rp_contractorproject/{id}',['as' => 'postContractorrpproject',         'uses' => 'Supply\SupplyRPProjectController@post']);
Route::any('contractorrpprojectapply1/{id}',['as' => 'contractorrpprojectapplyList','uses' => 'Supply\SupplyRPProjectController@setIdentityLicense']);
//承攬商成員工程身分申請(作廢)
Route::any('rp_contractormemberidentity',      ['as' => 'contractorrpmemberidentityList',   'uses' => 'Supply\SupplyRPMemberIdentityController@index']);
Route::get('rp_contractormemberidentity/{id}', ['as' => 'contractorrpmemberidentityEdit',   'uses' => 'Supply\SupplyRPMemberIdentityController@show']);
Route::get('rp_contractormemberidentity2/{id}',['as' => 'contractorrpmemberidentityEdit2',  'uses' => 'Supply\SupplyRPMemberIdentityController@show2']);
//Route::get('new_rp_contractormemberidentity',  ['as' => 'contractorrpmemberidentityCreate', 'uses' => 'Supply\SupplyRPMemberIdentityController@create']);
Route::post('rp_contractormemberidentity/{id}',['as' => 'postContractorrpmemberidentity',   'uses' => 'Supply\SupplyRPMemberIdentityController@post']);
//承攬商成員_工程案件之工程身分申請單
Route::any('rp_contractorprojectidentity',      ['as' => 'contractorrpprojectidentityList',   'uses' => 'Supply\SupplyRPProjectIdentityController@index']);
Route::get('rp_contractorprojectidentity/{id}', ['as' => 'contractorrpprojectidentityEdit',   'uses' => 'Supply\SupplyRPProjectIdentityController@show']);
Route::get('rp_contractorprojectidentity2/{id}',['as' => 'contractorrpprojectidentityEdit2',  'uses' => 'Supply\SupplyRPProjectIdentityController@show2']);
Route::post('rp_contractorprojectidentity/{id}',['as' => 'postContractorrpprojectidentity',   'uses' => 'Supply\SupplyRPProjectIdentityController@post']);
//成員證照申請單
Route::any('rp_contractormemberlicense',      ['as' => 'contractorrpmemberlicenseList',   'uses' => 'Supply\SupplyRPMemberIdentityController@index']);
Route::get('rp_contractormemberlicense/{id}', ['as' => 'contractorrpmemberlicenseEdit',   'uses' => 'Supply\SupplyRPMemberIdentityController@show']);
Route::get('rp_contractormemberlicense2/{id}',['as' => 'contractorrpmemberlicenseEdit2',  'uses' => 'Supply\SupplyRPMemberIdentityController@show2']);
//Route::get('new_rp_contractormemberlicense',  ['as' => 'contractorrpmemberlicenseCreate', 'uses' => 'Supply\SupplyRPMemberIdentityController@create']);
Route::post('rp_contractormemberlicense/{id}',['as' => 'postContractorrpmemberlicense',   'uses' => 'Supply\SupplyRPMemberIdentityController@post']);
//成員帳號開通申請單
Route::any('rp_contractorapp',      ['as' => 'contractorrpappList',   'uses' => 'Supply\SupplyRPBcustController@index']);
Route::get('rp_contractorapp/{id}', ['as' => 'contractorrpappEdit',   'uses' => 'Supply\SupplyRPBcustController@show']);
Route::get('rp_contractorapp2/{id}',['as' => 'contractorrpappEdit2',  'uses' => 'Supply\SupplyRPBcustController@show2']);
//Route::get('new_rp_contractorapp',  ['as' => 'contractorrpappCreate', 'uses' => 'Supply\SupplyRPBcustController@create']);
Route::post('rp_contractorapp/{id}',['as' => 'postContractorrpapp',   'uses' => 'Supply\SupplyRPBcustController@post']);
//人員違規申訴單
Route::any('rp_eviolationcomplain',           ['as' => 'contractorVcomplainList',   'uses' => 'Supply\SupplyViolationComplainController@index']);
Route::get('rp_eviolationcomplain/{id}',      ['as' => 'contractorVcomplainEdit',   'uses' => 'Supply\SupplyViolationComplainController@show']);
Route::any('new_rp_eviolationcomplain',       ['as' => 'contractorVcomplainCreate', 'uses' => 'Supply\SupplyViolationComplainController@create']);
Route::post('rp_eviolationcomplain/{id}',     ['as' => 'postContractorVcomplain',   'uses' => 'Supply\SupplyViolationComplainController@post']);
//[申請]新證照類型
Route::any('rp_contractornewlicense',           ['as' => 'contractorNewlicenseList',   'uses' => 'Supply\SupplyRPNewLicenseController@index']);
Route::get('rp_contractornewlicense/{id}',      ['as' => 'contractorNewlicenseEdit',   'uses' => 'Supply\SupplyRPNewLicenseController@show']);
Route::get('rp_contractornewlicense2/{id}',     ['as' => 'contractorNewlicenseEdit2',   'uses' => 'Supply\SupplyRPNewLicenseController@show2']);
//Route::any('new_rp_eviolationcomplain',       ['as' => 'contractorNewlicenseCreate', 'uses' => 'Supply\SupplyRPNewLicenseController@create']);
Route::post('rp_contractornewlicense/{id}',     ['as' => 'postContractorNewlicense',   'uses' => 'Supply\SupplyRPNewLicenseController@post']);
//申請 臨時申請單/過夜單/加班單
Route::any('contractorrpdoor1',           ['as' => 'contractorrpdoor1List',   'uses' => 'Supply\SupplyRPDoor1Controller@index']);
Route::get('contractorrpdoor1/{id}',      ['as' => 'contractorrpdoor1Edit',   'uses' => 'Supply\SupplyRPDoor1Controller@show']);
Route::get('contractorrpdoor1a/{id}',     ['as' => 'contractorrpdoor1Edit2',   'uses' => 'Supply\SupplyRPDoor1Controller@show2']);
Route::any('new_contractorrpdoor1',       ['as' => 'contractorrpdoor1Create', 'uses' => 'Supply\SupplyRPDoor1Controller@create']);
Route::post('contractorrpdoor1/{id}',     ['as' => 'postContractorrpdoor1',   'uses' => 'Supply\SupplyRPDoor1Controller@post']);

//====================================廠區=======================================//
//Find
Route::get('findLocal',      ['as' => 'findLocal',   'uses' => 'Factory\FindLocalController@findLocal']);
//廠區
Route::get('factory',      ['as' => 'factoryList',   'uses' => 'Factory\FactoryController@index']);
Route::get('factory/{id}', ['as' => 'factoryEdit',   'uses' => 'Factory\FactoryController@show']);
Route::get('new_factory',  ['as' => 'factoryCreate', 'uses' => 'Factory\FactoryController@create']);
Route::post('factory/{id}',['as' => 'postFactory',   'uses' => 'Factory\FactoryController@post']);

//廠區->轄區部門
Route::any('factorydept',      ['as' => 'factorydeptList',   'uses' => 'Factory\FactoryDeptController@index']);
Route::get('factorydept/{id}', ['as' => 'factorydeptEdit',   'uses' => 'Factory\FactoryDeptController@show']);
Route::get('new_factorydept',  ['as' => 'factorydeptCreate', 'uses' => 'Factory\FactoryDeptController@create']);
Route::post('factorydept/{id}',['as' => 'postFactoryDept',   'uses' => 'Factory\FactoryDeptController@post']);
//廠區->場地
Route::any('factorylocal',      ['as' => 'factorylocalList',   'uses' => 'Factory\FactoryLocalController@index']);
Route::get('factorylocal/{id}', ['as' => 'factorylocalEdit',   'uses' => 'Factory\FactoryLocalController@show']);
Route::get('new_factorylocal',  ['as' => 'factorylocalCreate', 'uses' => 'Factory\FactoryLocalController@create']);
Route::post('factorylocal/{id}',['as' => 'postFactoryLocal',   'uses' => 'Factory\FactoryLocalController@post']);
//廠區->場地->施工地點
Route::any('factorydevice',      ['as' => 'factorydeviceList',   'uses' => 'Factory\FactoryDeviceController@index']);
Route::get('factorydevice/{id}', ['as' => 'factorydeviceEdit',   'uses' => 'Factory\FactoryDeviceController@show']);
Route::get('new_factorydevice',  ['as' => 'factorydeviceCreate', 'uses' => 'Factory\FactoryDeviceController@create']);
Route::post('factorydevice/{id}',['as' => 'postFactoryDevice',   'uses' => 'Factory\FactoryDeviceController@post']);
//廠區->門禁工作站
Route::any('factorydoor',      ['as' => 'factorydoorList',   'uses' => 'Factory\FactoryDoorController@index']);
Route::get('factorydoor/{id}', ['as' => 'factorydoorEdit',   'uses' => 'Factory\FactoryDoorController@show']);
Route::get('new_factorydoor',  ['as' => 'factorydoorCreate', 'uses' => 'Factory\FactoryDoorController@create']);
Route::post('factorydoor/{id}',['as' => 'postFactoryDoor',   'uses' => 'Factory\FactoryDoorController@post']);

//====================================車輛=======================================//
//車輛分類
Route::get('cartype',      ['as' => 'cartypeList',   'uses' => 'Factory\CarTypeController@index']);
Route::get('cartype/{id}', ['as' => 'cartypeEdit',   'uses' => 'Factory\CarTypeController@show']);
Route::get('new_cartype',  ['as' => 'cartypeCreate', 'uses' => 'Factory\CarTypeController@create']);
Route::post('cartype/{id}',['as' => 'postCartype',   'uses' => 'Factory\CarTypeController@post']);
//職員車輛
//Route::get('membercar',      ['as' => 'membercarList',   'uses' => 'Emp\MemberCarController@index']);
//Route::get('membercar/{id}', ['as' => 'membercarEdit',   'uses' => 'Emp\MemberCarController@show']);
//Route::get('new_membercar',  ['as' => 'membercarCreate', 'uses' => 'Emp\MemberCarController@create']);
//Route::post('membercar/{id}',['as' => 'postMembercar',   'uses' => 'Emp\MemberCarController@post']);
//車輛登記作業
Route::any('buildcar',          ['as' => 'buildCarList',   'uses' => 'Engineering\EngineeringCarBuildController@index']);
Route::any('new_buildcar',     ['as' => 'postBuildcar',   'uses' => 'Engineering\EngineeringCarBuildController@post']);
//承攬商車輛
Route::any('contractorcar',      ['as' => 'contractorcarList',   'uses' => 'Supply\SupplyCarController@index']);
Route::get('contractorcar/{id}', ['as' => 'contractorcarEdit',   'uses' => 'Supply\SupplyCarController@show']);
Route::get('new_contractorcar',  ['as' => 'contractorcarCreate', 'uses' => 'Supply\SupplyCarController@create']);
Route::post('contractorcar/{id}',['as' => 'postContractorcar',   'uses' => 'Supply\SupplyCarController@post']);
//承攬商車輛申請單
Route::any('rp_contractorcar',      ['as' => 'contractorrpcarList',   'uses' => 'Supply\SupplyRPCarController@index']);
Route::get('rp_contractorcar/{id}', ['as' => 'contractorrpcarEdit',   'uses' => 'Supply\SupplyRPCarController@show']);
Route::get('rp_contractorcar2/{id}',['as' => 'contractorrpcarEdit2',  'uses' => 'Supply\SupplyRPCarController@show2']);
Route::get('new_rp_contractorcar',  ['as' => 'contractorrpcarCreate', 'uses' => 'Supply\SupplyRPCarController@create']);
Route::post('rp_contractorcar/{id}',['as' => 'postContractorrpcar',   'uses' => 'Supply\SupplyRPCarController@post']);


//====================================訪客=======================================//

//[訪客]訪客來訪記錄
Route::any('guest',      ['as' => 'guestList',   'uses' => 'Factory\GuestController@index']);
Route::get('guest/{id}', ['as' => 'guestEdit',   'uses' => 'Factory\GuestController@show']);
Route::get('new_guest',  ['as' => 'guestCreate', 'uses' => 'Factory\GuestController@create']);
Route::post('guest/{id}',['as' => 'postGuest',   'uses' => 'Factory\GuestController@post']);
//[訪客]訪客來訪記錄
Route::any('guest_record',      ['as' => 'guestRecordList',   'uses' => 'Factory\GuestRecordController@index']);
Route::get('guest_record/{id}', ['as' => 'guestRecordEdit',   'uses' => 'Factory\GuestRecordController@show']);
Route::get('new_guest_record',  ['as' => 'guestRecordCreate', 'uses' => 'Factory\GuestRecordController@create']);
Route::post('guest_record/{id}',['as' => 'postGuestRecord',   'uses' => 'Factory\GuestRecordController@post']);

//====================================RFID=======================================//
//RFID
Route::any('rfid',      ['as' => 'rfidList',   'uses' => 'Factory\RFIDController@index']);
Route::get('rfid/{id}', ['as' => 'rfidEdit',   'uses' => 'Factory\RFIDController@show']);
Route::get('new_rfid',  ['as' => 'rfidCreate', 'uses' => 'Factory\RFIDController@create']);
Route::post('rfid/{id}',['as' => 'postRFID',   'uses' => 'Factory\RFIDController@post']);
//Excel匯入
Route::get( 'exceltorfid',['as' => 'ImportToRFID',      'uses' => 'Factory\ImportToRFIDController@index']);
Route::post('exceltorfid',['as' => 'postImportToRFID',  'uses' => 'Factory\ImportToRFIDController@post']);
//Excel匯入
Route::get( 'exceltorfidpair1',['as' => 'ImportToRFID1',  'uses' => 'Factory\ImportToRFIDPairController@index']);
Route::post('exceltorfidpair1',['as' => 'ImportToRFID1',  'uses' => 'Factory\ImportToRFIDPairController@post']);
//RFID配對
Route::get('rfidpair',      ['as' => 'rfidpairList',   'uses' => 'Factory\RFIDPairController@index']);
Route::get('rfidpair/{id}', ['as' => 'rfidpairEdit',   'uses' => 'Factory\RFIDPairController@show']);
Route::get('new_rfidpair/{id}',  ['as' => 'rfidpairCreate', 'uses' => 'Factory\RFIDPairController@create']);
Route::post('rfidpair/{id}',['as' => 'postRFIDpair',   'uses' => 'Factory\RFIDPairController@post']);
//RFID分類
Route::get('rfidtype',      ['as' => 'rfidtypeList',   'uses' => 'Factory\RFIDTypeController@index']);
Route::get('rfidtype/{id}', ['as' => 'rfidtypeEdit',   'uses' => 'Factory\RFIDTypeController@show']);
Route::get('new_rfidtype',  ['as' => 'rfidtypeCreate', 'uses' => 'Factory\RFIDTypeController@create']);
Route::post('rfidtype/{id}',['as' => 'postRFIDtype',   'uses' => 'Factory\RFIDTypeController@post']);
//RFID分類
Route::get('rfidlock',      ['as' => 'rfidlockList',   'uses' => 'Factory\PaitCardLockMenController@index']);
Route::get('rfidlock/{id}', ['as' => 'rfidlockEdit',   'uses' => 'Factory\PaitCardLockMenController@show']);

//====================================工程案件=======================================//
//FIND
Route::get('findEngineering',      ['as' => 'findEngineering',   'uses' => 'Engineering\FindEngineeringController@findEngineering']);
//工程案件
Route::any('engineering',      ['as' => 'engineeringList',   'uses' => 'Engineering\EngineeringController@index']);
Route::get('engineering/{id}', ['as' => 'engineeringEdit',   'uses' => 'Engineering\EngineeringController@show']);
Route::get('new_engineering',  ['as' => 'engineeringCreate', 'uses' => 'Engineering\EngineeringController@create']);
Route::post('engineering/{id}',['as' => 'postEngineering',   'uses' => 'Engineering\EngineeringController@post']);
//工程案件-證照
Route::get('engineeringlicense',           ['as' => 'engineeringlicenseList',   'uses' => 'Engineering\EngineeringLicenseController@index']);
Route::get('engineeringlicense/{id}',      ['as' => 'engineeringlicenseEdit',   'uses' => 'Engineering\EngineeringLicenseController@show']);
Route::get('new_engineeringlicense/{id}',  ['as' => 'engineeringlicenseCreate', 'uses' => 'Engineering\EngineeringLicenseController@create']);
Route::post('engineeringlicense/{id}',     ['as' => 'postEngineeringlicense',   'uses' => 'Engineering\EngineeringLicenseController@post']);
//工程案件-教育訓練
Route::get('engineeringcourse',           ['as' => 'engineeringcourseList',   'uses' => 'Engineering\EngineeringCourseController@index']);
Route::get('engineeringcourse/{id}',      ['as' => 'engineeringcourseEdit',   'uses' => 'Engineering\EngineeringCourseController@show']);
Route::get('new_engineeringcourse/{id}',  ['as' => 'engineeringcourseCreate', 'uses' => 'Engineering\EngineeringCourseController@create']);
Route::post('engineeringcourse/{id}',     ['as' => 'postEngineeringcourse',   'uses' => 'Engineering\EngineeringCourseController@post']);
//工程案件-承攬商成員
Route::get('engineeringmember',           ['as' => 'engineeringmemberList',   'uses' => 'Engineering\EngineeringMemberController@index']);
Route::get('engineeringmember/{id}',      ['as' => 'engineeringmemberEdit',   'uses' => 'Engineering\EngineeringMemberController@show']);
Route::get('new_engineeringmember/{id}',  ['as' => 'engineeringmemberCreate', 'uses' => 'Engineering\EngineeringMemberController@create']);
Route::post('engineeringmember/{id}',     ['as' => 'postEngineeringmember',   'uses' => 'Engineering\EngineeringMemberController@post']);
Route::any('engineeringmemberroster/{id}',['as' => 'engineeringmemberRept',   'uses' => 'Engineering\EngineeringMemberController@report']);
//工程案件-承攬商廠區
Route::get('engineeringfactory',           ['as' => 'engineeringfactoryList',   'uses' => 'Engineering\EngineeringFactoryController@index']);
Route::get('engineeringfactory/{id}',      ['as' => 'engineeringfactoryEdit',   'uses' => 'Engineering\EngineeringFactoryController@show']);
Route::any('new_engineeringfactory/{id}',  ['as' => 'engineeringfactoryCreate', 'uses' => 'Engineering\EngineeringFactoryController@create']);
Route::post('engineeringfactory/{id}',     ['as' => 'postEngineeringfactory',   'uses' => 'Engineering\EngineeringFactoryController@post']);
//工程案件-監造部門
Route::get('engineeringdept',           ['as' => 'engineeringdeptList',   'uses' => 'Engineering\EngineeringDeptController@index']);
Route::get('engineeringdept/{id}',      ['as' => 'engineeringdeptEdit',   'uses' => 'Engineering\EngineeringDeptController@show']);
Route::any('new_engineeringdept/{id}',  ['as' => 'engineeringdeptCreate', 'uses' => 'Engineering\EngineeringDeptController@create']);
Route::post('engineeringdept/{id}',     ['as' => 'postEngineeringdept',   'uses' => 'Engineering\EngineeringDeptController@post']);
//工程案件-車輛
Route::get('engineeringcar',           ['as' => 'engineeringcarList',   'uses' => 'Engineering\EngineeringCarController@index']);
Route::get('engineeringcar/{id}',      ['as' => 'engineeringcarEdit',   'uses' => 'Engineering\EngineeringCarController@show']);
Route::any('new_engineeringcar/{id}',  ['as' => 'engineeringcarCreate', 'uses' => 'Engineering\EngineeringCarController@create']);
Route::post('engineeringcar/{id}',     ['as' => 'postEngineeringcar',   'uses' => 'Engineering\EngineeringCarController@post']);
//工程案件
Route::get('engineering',       ['as' => 'engineeringList',   'uses' => 'Engineering\EngineeringController@index']);
Route::get('engineering/{id}',  ['as' => 'engineeringEdit',   'uses' => 'Engineering\EngineeringController@show']);
Route::get('new_engineering',   ['as' => 'engineeringCreate', 'uses' => 'Engineering\EngineeringController@create']);
Route::get('change_engineering',['as' => 'engineeringChange', 'uses' => 'Engineering\EngineeringController@change']);
Route::post('engineering/{id}', ['as' => 'postEngineering',   'uses' => 'Engineering\EngineeringController@post']);
//工程案件分類
Route::get('engineeringtype',      ['as' => 'engineeringtypeList',   'uses' => 'Engineering\EngineeringTypeController@index']);
Route::get('engineeringtype/{id}', ['as' => 'engineeringtypeEdit',   'uses' => 'Engineering\EngineeringTypeController@show']);
Route::get('new_engineeringtype',  ['as' => 'engineeringtypeCreate', 'uses' => 'Engineering\EngineeringTypeController@create']);
Route::post('engineeringtype/{id}',['as' => 'postEngineeringType',   'uses' => 'Engineering\EngineeringTypeController@post']);

//證照
Route::get('elicense',      ['as' => 'elicenseList',   'uses' => 'Engineering\LicenseController@index']);
Route::get('elicense/{id}', ['as' => 'elicenseEdit',   'uses' => 'Engineering\LicenseController@show']);
Route::get('new_elicense',  ['as' => 'elicenseCreate', 'uses' => 'Engineering\LicenseController@create']);
Route::post('elicense/{id}',['as' => 'postELicense',   'uses' => 'Engineering\LicenseController@post']);
//證照分類
Route::get('elicensetype',      ['as' => 'elicensetypeList',   'uses' => 'Engineering\LicenseTypeController@index']);
Route::get('elicensetype/{id}', ['as' => 'elicensetypeEdit',   'uses' => 'Engineering\LicenseTypeController@show']);
Route::get('new_elicensetype',  ['as' => 'elicensetypeCreate', 'uses' => 'Engineering\LicenseTypeController@create']);
Route::post('elicensetype/{id}',['as' => 'postELicenseType',   'uses' => 'Engineering\LicenseTypeController@post']);

//====================================違規=======================================//
//人員違規
Route::any('eviolationcontractor',      ['as' => 'eviolationcontractorList',   'uses' => 'Engineering\ViolationContractorController@index']);
Route::get('eviolationcontractor/{id}', ['as' => 'eviolationcontractorEdit',   'uses' => 'Engineering\ViolationContractorController@show']);
Route::any('new_eviolationcontractor',  ['as' => 'eviolationcontractorCreate', 'uses' => 'Engineering\ViolationContractorController@create']);
Route::post('eviolationcontractor/{id}',['as' => 'postEViolationcontractor',   'uses' => 'Engineering\ViolationContractorController@post']);
Route::get('eviolationcontractorexcel',['as' => 'excelEViolationcontractor',   'uses' => 'Engineering\ViolationContractorController@downExcel']);
//違規事項
Route::get('eviolation',      ['as' => 'eviolationList',   'uses' => 'Engineering\ViolationController@index']);
Route::get('eviolation/{id}', ['as' => 'eviolationEdit',   'uses' => 'Engineering\ViolationController@show']);
Route::get('new_eviolation',  ['as' => 'eviolationCreate', 'uses' => 'Engineering\ViolationController@create']);
Route::post('eviolation/{id}',['as' => 'postEViolation',   'uses' => 'Engineering\ViolationController@post']);
//違規分類
Route::get('eviolationtype',      ['as' => 'eviolationtypeList',   'uses' => 'Engineering\ViolationTypeController@index']);
Route::get('eviolationtype/{id}', ['as' => 'eviolationtypeEdit',   'uses' => 'Engineering\ViolationTypeController@show']);
Route::get('new_eviolationtype',  ['as' => 'eviolationtypeCreate', 'uses' => 'Engineering\ViolationTypeController@create']);
Route::post('eviolationtype/{id}',['as' => 'postEViolationtype',   'uses' => 'Engineering\ViolationTypeController@post']);
//違規法條
Route::get('eviolationlaw',      ['as' => 'eviolationlawList',   'uses' => 'Engineering\ViolationLawController@index']);
Route::get('eviolationlaw/{id}', ['as' => 'eviolationlawEdit',   'uses' => 'Engineering\ViolationLawController@show']);
Route::get('new_eviolationlaw',  ['as' => 'eviolationlawCreate', 'uses' => 'Engineering\ViolationLawController@create']);
Route::post('eviolationlaw/{id}',['as' => 'postEviolationlaw',   'uses' => 'Engineering\ViolationLawController@post']);
//違規罰則
Route::get('eviolationpunish',      ['as' => 'eviolationpunishList',   'uses' => 'Engineering\ViolationPunishController@index']);
Route::get('eviolationpunish/{id}', ['as' => 'eviolationpunishEdit',   'uses' => 'Engineering\ViolationPunishController@show']);
Route::get('new_eviolationpunish',  ['as' => 'eviolationpunishCreate', 'uses' => 'Engineering\ViolationPunishController@create']);
Route::post('eviolationpunish/{id}',['as' => 'postEviolationpunish',   'uses' => 'Engineering\ViolationPunishController@post']);

//====================================教育訓練=======================================//
//FIND
Route::get('findCourse',      ['as' => 'findCourse',   'uses' => 'Engineering\FindEngineeringController@findCourse']);
//課程
Route::get('ecourse',      ['as' => 'ecourseList',   'uses' => 'Engineering\CourseController@index']);
Route::get('ecourse/{id}', ['as' => 'ecourseEdit',   'uses' => 'Engineering\CourseController@show']);
Route::get('new_ecourse',  ['as' => 'ecourseCreate', 'uses' => 'Engineering\CourseController@create']);
Route::post('ecourse/{id}',['as' => 'postECourse',   'uses' => 'Engineering\CourseController@post']);
//課程分類
Route::get('ecoursetype',      ['as' => 'ecoursetypeList',   'uses' => 'Engineering\CourseTypeController@index']);
Route::get('ecoursetype/{id}', ['as' => 'ecoursetypeEdit',   'uses' => 'Engineering\CourseTypeController@show']);
Route::get('new_ecoursetype',  ['as' => 'ecoursetypeCreate', 'uses' => 'Engineering\CourseTypeController@create']);
Route::post('ecoursetype/{id}',['as' => 'postECoursetype',   'uses' => 'Engineering\CourseTypeController@post']);
//開課
Route::any('etraning',      ['as' => 'etraningList',        'uses' => 'Engineering\TraningController@index']);
Route::get('etraning/{id}', ['as' => 'etraningEdit',        'uses' => 'Engineering\TraningController@show']);
Route::get('new_etraning',  ['as' => 'etraningCreate',      'uses' => 'Engineering\TraningController@create']);
Route::post('etraning/{id}',['as' => 'postETraning',        'uses' => 'Engineering\TraningController@post']);
Route::any('etraningroster/{id}',['as' => 'etraningRept',   'uses' => 'Engineering\TraningController@report']);
//開課時段
Route::get('etraningtime',      ['as' => 'etraningtimeList',   'uses' => 'Engineering\TraningTimeController@index']);
Route::get('etraningtime/{id}', ['as' => 'etraningtimeEdit',   'uses' => 'Engineering\TraningTimeController@show']);
Route::get('new_etraningtime/{id}',  ['as' => 'etraningtimeCreate', 'uses' => 'Engineering\TraningTimeController@create']);
Route::post('etraningtime/{id}',['as' => 'postETraningtime',   'uses' => 'Engineering\TraningTimeController@post']);
//開課學員
Route::any('etraningmember',            ['as' => 'etraningmemberList',   'uses' => 'Engineering\TraningMemberController@index']);
Route::get('etraningmemberorder',       ['as' => 'etraningmemberOrder',  'uses' => 'Engineering\TraningMemberController@print']);
Route::get('etraningmember/{id}',       ['as' => 'etraningmemberEdit',   'uses' => 'Engineering\TraningMemberController@show']);
Route::any('new_etraningmember',        ['as' => 'etraningmemberCreate', 'uses' => 'Engineering\TraningMemberController@create']);
Route::post('etraningmember/{id}',      ['as' => 'postETraningmember',   'uses' => 'Engineering\TraningMemberController@post']);
//開課學員_特定人員
Route::any('etraningmember2',            ['as' => 'etraningmemberList2',   'uses' => 'Engineering\TraningMemberSelfController@index']);
Route::any('etraningsupplymember',       ['as' => 'etraningmemberList3',   'uses' => 'Engineering\TraningSupplyMemberController@index']);
//開課學員
Route::any('exa_etraningmember',            ['as' => 'exaetraningmemberList',   'uses' => 'Engineering\TraningMemberExaController@index']);
//Route::get('exa_etraningmemberorder',       ['as' => 'exaetraningmemberOrder',  'uses' => 'Engineering\TraningMemberExaController@print']);
Route::get('exa_etraningmember/{id}',       ['as' => 'exaetraningmemberEdit',   'uses' => 'Engineering\TraningMemberExaController@show']);
//Route::any('new_etraningmember',        ['as' => 'etraningmemberCreate', 'uses' => 'Engineering\TraningMemberController@create']);
Route::post('exa_etraningmember/{id}',      ['as' => 'postExaETraningmember',   'uses' => 'Engineering\TraningMemberExaController@post']);
//審查 報名
Route::any('rp_etraning',      ['as' => 'erptraningList',       'uses' => 'Supply\SupplyRPTraningController@index']);
Route::get('rp_etraning/{id}', ['as' => 'erptraningEdit',       'uses' => 'Supply\SupplyRPTraningController@show']);
//Route::get('new_rp_etraning/{id}',  ['as' => 'erptraningCreate','uses' => 'Supply\SupplyRPTraningController@create']);
Route::post('rp_etraning/{id}',['as' => 'postERPTraning',       'uses' => 'Supply\SupplyRPTraningController@post']);
//它廠教育訓練建置作業
Route::any('exa_etraningmember2',            ['as' => 'exaetraningmemberList2',   'uses' => 'Engineering\TraningMemberImportController@create']);
Route::post('exa_etraningmember2/{id}',       ['as' => 'postExaETraningmember2',   'uses' => 'Engineering\TraningMemberImportController@post']);

//====================================工作許可證=======================================//
//工作許可證種類
Route::get('workpermitkind',      ['as' => 'workpermitkindList',   'uses' => 'WorkPermit\WorkPermitKindController@index']);
Route::get('workpermitkind/{id}', ['as' => 'workpermitkindEdit',   'uses' => 'WorkPermit\WorkPermitKindController@show']);
Route::get('new_workpermitkind',  ['as' => 'workpermitkindCreate', 'uses' => 'WorkPermit\WorkPermitKindController@create']);
Route::post('workpermitkind/{id}',['as' => 'postWorkpermitkind',   'uses' => 'WorkPermit\WorkPermitKindController@post']);
//工作許可證危險告知
Route::get('workpermitdanger',           ['as' => 'workpermitdangerList',   'uses' => 'WorkPermit\WorkPermitDangerController@index']);
Route::get('workpermitdanger/{id}',      ['as' => 'workpermitdangerEdit',   'uses' => 'WorkPermit\WorkPermitDangerController@show']);
Route::get('new_workpermitdanger',       ['as' => 'workpermitdangerCreate', 'uses' => 'WorkPermit\WorkPermitDangerController@create']);
Route::post('workpermitdanger/{id}',     ['as' => 'postWorkpermitdanger',   'uses' => 'WorkPermit\WorkPermitDangerController@post']);
//工作許可證工作項目
Route::get('workpermitworkitem',           ['as' => 'workpermitworkitemList',   'uses' => 'WorkPermit\WorkPermitWorkItemController@index']);
Route::get('workpermitworkitem/{id}',      ['as' => 'workpermitworkitemEdit',   'uses' => 'WorkPermit\WorkPermitWorkItemController@show']);
Route::get('new_workpermitworkitem/{id}',  ['as' => 'workpermitworkitemCreate', 'uses' => 'WorkPermit\WorkPermitWorkItemController@create']);
Route::post('workpermitworkitem/{id}',     ['as' => 'postWorkpermitworkitem',   'uses' => 'WorkPermit\WorkPermitWorkItemController@post']);
//工作許可證工作項目ｖｓ危險告知
Route::get('workpermitworkitemdanger',           ['as' => 'workpermitworkitemdangerList',   'uses' => 'WorkPermit\WorkPermitWorkItemDangerController@index']);
Route::get('workpermitworkitemdanger/{id}',      ['as' => 'workpermitworkitemdangerEdit',   'uses' => 'WorkPermit\WorkPermitWorkItemDangerController@show']);
Route::get('new_workpermitworkitemdanger/{id}',  ['as' => 'workpermitworkitemdangerCreate', 'uses' => 'WorkPermit\WorkPermitWorkItemDangerController@create']);
Route::post('workpermitworkitemdanger/{id}',     ['as' => 'postWorkpermitworkitemdanger',   'uses' => 'WorkPermit\WorkPermitWorkItemDangerController@post']);
//工作許可證工作項目ｖｓ附加檢點表
Route::get('workpermitworkitemcheck',           ['as' => 'workpermitworkitemcheckList',   'uses' => 'WorkPermit\WorkPermitWorkItemCehckController@index']);
Route::get('workpermitworkitemcheck/{id}',      ['as' => 'workpermitworkitemcheckEdit',   'uses' => 'WorkPermit\WorkPermitWorkItemCehckController@show']);
Route::get('new_workpermitworkitemcheck/{id}',  ['as' => 'workpermitworkitemcheckCreate', 'uses' => 'WorkPermit\WorkPermitWorkItemCehckController@create']);
Route::post('workpermitworkitemcheck/{id}',     ['as' => 'postWorkpermitworkitemcheck',   'uses' => 'WorkPermit\WorkPermitWorkItemCehckController@post']);


//工作許可證 設計
Route::get('workpermit',      ['as' => 'workpermitList',   'uses' => 'WorkPermit\WorkPermitController@index']);
Route::get('workpermit/{id}', ['as' => 'workpermitEdit',   'uses' => 'WorkPermit\WorkPermitController@show']);
Route::get('new_workpermit',  ['as' => 'workpermitCreate', 'uses' => 'WorkPermit\WorkPermitController@create']);
Route::post('workpermit/{id}',['as' => 'postWorkpermit',   'uses' => 'WorkPermit\WorkPermitController@post']);
//工作許可證-檢核項目
Route::get('workpermittopic',      ['as' => 'workpermittopicList',   'uses' => 'WorkPermit\WorkPermitTopicController@index']);
Route::get('workpermittopic/{id}', ['as' => 'workpermittopicEdit',   'uses' => 'WorkPermit\WorkPermitTopicController@show']);
Route::get('new_workpermittopic/{id}',  ['as' => 'workpermittopicCreate', 'uses' => 'WorkPermit\WorkPermitTopicController@create']);
Route::post('workpermittopic/{id}',['as' => 'postWorkpermittopic',   'uses' => 'WorkPermit\WorkPermitTopicController@post']);
//工作許可證-檢核選項
Route::get('workpermittopicoption',      ['as' => 'workpermittopicoptionList',   'uses' => 'WorkPermit\WorkPermitTopicOptionController@index']);
Route::get('workpermittopicoption/{id}', ['as' => 'workpermittopicoptionEdit',   'uses' => 'WorkPermit\WorkPermitTopicOptionController@show']);
Route::get('new_workpermittopicoption/{id}',  ['as' => 'workpermittopicoptionCreate', 'uses' => 'WorkPermit\WorkPermitTopicOptionController@create']);
Route::post('workpermittopicoption/{id}',['as' => 'postWorkpermittopicoption',   'uses' => 'WorkPermit\WorkPermitTopicOptionController@post']);

//工作許可證-流程顯示
Route::get('workpermitprocessshow',             ['as' => 'workpermitprocessshowList',   'uses' => 'WorkPermit\WorkPermitProcessShowController@index']);
Route::get('workpermitprocessshow/{id}',        ['as' => 'workpermitprocessshowEdit',   'uses' => 'WorkPermit\WorkPermitProcessShowController@show']);
Route::get('new_workpermitprocessshow/{id}',    ['as' => 'workpermitprocessshowCreate', 'uses' => 'WorkPermit\WorkPermitProcessShowController@create']);
Route::post('workpermitprocessshow/{id}',       ['as' => 'postWorkpermitprocessshow',   'uses' => 'WorkPermit\WorkPermitProcessShowController@post']);
//工作許可證-班別
Route::get('workpermitshift',             ['as' => 'workpermitshiftList',   'uses' => 'WorkPermit\WorkPermitShiftController@index']);
Route::get('workpermitshift/{id}',        ['as' => 'workpermitshiftEdit',   'uses' => 'WorkPermit\WorkPermitShiftController@show']);
Route::get('new_workpermitshift',    ['as' => 'workpermitshiftCreate', 'uses' => 'WorkPermit\WorkPermitShiftController@create']);
Route::post('workpermitshift/{id}',       ['as' => 'postWorkpermitshift',   'uses' => 'WorkPermit\WorkPermitShiftController@post']);


//工作許可證-工程身份
Route::get('workpermitidentity',      ['as' => 'workpermitidentityList',   'uses' => 'WorkPermit\WorkPermitIdentityController@index']);
Route::get('workpermitidentity/{id}', ['as' => 'workpermitidentityEdit',   'uses' => 'WorkPermit\WorkPermitIdentityController@show']);
Route::get('new_workpermitidentity/{id}',  ['as' => 'workpermitidentityCreate', 'uses' => 'WorkPermit\WorkPermitIdentityController@create']);
Route::post('workpermitidentity/{id}',['as' => 'postWorkpermitidentity',   'uses' => 'WorkPermit\WorkPermitIdentityController@post']);

//工作許可證-流程設計
Route::get('workpermitprocess',             ['as' => 'workpermitprocessList',   'uses' => 'WorkPermit\WorkPermitProcessController@index']);
Route::get('workpermitprocess/{id}',        ['as' => 'workpermitprocessEdit',   'uses' => 'WorkPermit\WorkPermitProcessController@show']);
Route::get('new_workpermitprocess/{id}',    ['as' => 'workpermitprocessCreate', 'uses' => 'WorkPermit\WorkPermitProcessController@create']);
Route::post('workpermitprocess/{id}',       ['as' => 'postWorkpermitprocess',   'uses' => 'WorkPermit\WorkPermitProcessController@post']);
//工作許可證-流程-簽核對象設計
Route::get('workpermitprocesstarget',             ['as' => 'workpermitprocesstargetList',   'uses' => 'WorkPermit\WorkPermitProcessTargetController@index']);
Route::get('workpermitprocesstarget/{id}',        ['as' => 'workpermitprocesstargetEdit',   'uses' => 'WorkPermit\WorkPermitProcessTargetController@show']);
Route::get('new_workpermitprocesstarget/{id}',    ['as' => 'workpermitprocesstargetCreate', 'uses' => 'WorkPermit\WorkPermitProcessTargetController@create']);
Route::post('workpermitprocesstarget/{id}',       ['as' => 'postWorkpermitprocesstarget',   'uses' => 'WorkPermit\WorkPermitProcessTargetController@post']);
//工作許可證-流程-檢核項目設計
Route::get('workpermitprocesstopic',             ['as' => 'workpermitprocesstopicList',   'uses' => 'WorkPermit\WorkPermitProcessTopicController@index']);
Route::get('workpermitprocesstopic/{id}',        ['as' => 'workpermitprocesstopicEdit',   'uses' => 'WorkPermit\WorkPermitProcessTopicController@show']);
Route::get('new_workpermitprocesstopic/{id}',    ['as' => 'workpermitprocesstopicCreate', 'uses' => 'WorkPermit\WorkPermitProcessTopicController@create']);
Route::post('workpermitprocesstopic/{id}',       ['as' => 'postWorkpermitprocesstopic',   'uses' => 'WorkPermit\WorkPermitProcessTopicController@post']);


//檢點單
Route::get('workcheck',      ['as' => 'workcheckList',   'uses' => 'WorkPermit\WorkCheckController@index']);
Route::get('workcheck/{id}', ['as' => 'workcheckEdit',   'uses' => 'WorkPermit\WorkCheckController@show']);
Route::get('new_workcheck',  ['as' => 'workcheckCreate', 'uses' => 'WorkPermit\WorkCheckController@create']);
Route::post('workcheck/{id}',['as' => 'postWorkcheck',   'uses' => 'WorkPermit\WorkCheckController@post']);
//檢點單-種類
Route::get('workcheckkind',      ['as' => 'workcheckkindList',   'uses' => 'WorkPermit\WorkCheckKindController@index']);
Route::get('workcheckkind/{id}', ['as' => 'workcheckkindEdit',   'uses' => 'WorkPermit\WorkCheckKindController@show']);
Route::get('new_workcheckkind',  ['as' => 'workcheckkindCreate', 'uses' => 'WorkPermit\WorkCheckKindController@create']);
Route::post('workcheckkind/{id}',['as' => 'postWorkcheckkind',   'uses' => 'WorkPermit\WorkCheckKindController@post']);
//檢點單-種類對應之檔案
Route::get('workcheckkindfile',      ['as' => 'workcheckkindfileList',   'uses' => 'WorkPermit\WorkCheckKindFileController@index']);
Route::get('workcheckkindfile/{id}', ['as' => 'workcheckkindfileEdit',   'uses' => 'WorkPermit\WorkCheckKindFileController@show']);
Route::get('new_workcheckkindfile',  ['as' => 'workcheckkindfileCreate', 'uses' => 'WorkPermit\WorkCheckKindFileController@create']);
Route::post('workcheckkindfile/{id}',['as' => 'postWorkcheckkindfile',   'uses' => 'WorkPermit\WorkCheckKindFileController@post']);
//檢點單-檢核項目
Route::get('workchecktopic',      ['as' => 'workchecktopicList',   'uses' => 'WorkPermit\WorkCheckTopicController@index']);
Route::get('workchecktopic/{id}', ['as' => 'workchecktopicEdit',   'uses' => 'WorkPermit\WorkCheckTopicController@show']);
Route::get('new_workchecktopic/{id}',  ['as' => 'workchecktopicCreate', 'uses' => 'WorkPermit\WorkCheckTopicController@create']);
Route::post('workchecktopic/{id}',['as' => 'postWorkchecktopic',   'uses' => 'WorkPermit\WorkCheckTopicController@post']);
//檢點單-檢核選項
Route::get('workchecktopicoption',      ['as' => 'workchecktopicoptionList',   'uses' => 'WorkPermit\WorkCheckTopicOptionController@index']);
Route::get('workchecktopicoption/{id}', ['as' => 'workchecktopicoptionEdit',   'uses' => 'WorkPermit\WorkCheckTopicOptionController@show']);
Route::get('new_workchecktopicoption/{id}',  ['as' => 'workchecktopicoptionCreate', 'uses' => 'WorkPermit\WorkCheckTopicOptionController@create']);
Route::post('workchecktopicoption/{id}',['as' => 'postWorkchecktopicoption',   'uses' => 'WorkPermit\WorkCheckTopicOptionController@post']);

//工作許可證檢核項目類別
Route::get('workpermittopictype',      ['as' => 'workpermittopictypeList',   'uses' => 'WorkPermit\WorkPermitTopicTypeController@index']);
Route::get('workpermittopictype/{id}', ['as' => 'workpermitopictypeEdit',   'uses' => 'WorkPermit\WorkPermitTopicTypeController@show']);
Route::get('new_workpermittopictype',  ['as' => 'workpermitopictypeCreate', 'uses' => 'WorkPermit\WorkPermitTopicTypeController@create']);
Route::post('workpermittopictype/{id}',['as' => 'postWorkpermittopictype',   'uses' => 'WorkPermit\WorkPermitTopicTypeController@post']);

//FIND
Route::get('findPermit',        ['as' => 'findPermit',      'uses' => 'WorkPermit\FindWorkPermitController@findPermit']);
Route::get('findPermitItem',    ['as' => 'findPermitItem',  'uses' => 'WorkPermit\FindWorkPermitController@findWorkItem']);
Route::get('findPermitWorker',  ['as' => 'findPermitWorker','uses' => 'WorkPermit\FindWorkPermitController@findPermitWork']);
Route::get('findPermitWorker2', ['as' => 'findPermitWorker2','uses' => 'WorkPermit\FindWorkPermitController@findPermitWorkItem']);
Route::get('findPermitWorker3', ['as' => 'findPermitWorker3','uses' => 'WorkPermit\FindWorkPermitController@findPermitWorkCheck']);
Route::get('findPermitWorker4', ['as' => 'findPermitWorker4','uses' => 'WorkPermit\FindWorkPermitController@findPermitWorkDanger']);
Route::get('findPermitWorker5', ['as' => 'findPermitWorker5','uses' => 'WorkPermit\FindWorkPermitController@findPermitWorkLine']);
//工作許可證申請/執行單
Route::any('wpworkorder',           ['as' => 'wpworkorderList',   'uses' => 'WorkPermit\WorkPermitWorkOrderController@index']);
Route::get('wpworkorder/{id}',      ['as' => 'wpworkorderEdit',   'uses' => 'WorkPermit\WorkPermitWorkOrderController@show']);
//Route::any('new_wpworkorder/{id}',  ['as' => 'wpworkorderCreate', 'uses' => 'WorkPermit\WorkPermitWorkOrderController@create']);
Route::post('wpworkorder/{id}',     ['as' => 'postWpWorkOrder',   'uses' => 'WorkPermit\WorkPermitWorkOrderController@post']);
//列印
Route::any('printpermit',           ['as' => 'printpermit',   'uses' => 'WorkPermit\PermitPrintController@index']);

//審查 工作許可證申請/執行單
Route::any('exa_wpworkorder',           ['as' => 'exawpworkorderList',   'uses' => 'WorkPermit\WorkPermitRPWorkOrderController@index']);
Route::get('exa_wpworkorder/{id}',      ['as' => 'exawpworkorderEdit',   'uses' => 'WorkPermit\WorkPermitRPWorkOrderController@show']);
//Route::any('new_wpworkorder/{id}',  ['as' => 'wpworkorderCreate', 'uses' => 'WorkPermit\WorkPermitWorkOrderController@create']);
Route::post('exa_wpworkorder/{id}',     ['as' => 'postExaWpWorkOrder',   'uses' => 'WorkPermit\WorkPermitRPWorkOrderController@post']);

//工作許可證申請/執行單
Route::any('exa_wpworkorder2',           ['as' => 'exawpworkorderList2',   'uses' => 'WorkPermit\WorkPermitRP2WorkOrderController@index']);
Route::get('exa_wpworkorder2/{id}',      ['as' => 'exawpworkorderEdit2',   'uses' => 'WorkPermit\WorkPermitRP2WorkOrderController@show']);
Route::any('edit_wpworkorder2/{id}',     ['as' => 'postEditWpWorkOrder2', 'uses' => 'WorkPermit\WorkPermitRP2WorkOrderController@EditWorkTime']);
Route::post('exa_wpworkorder2/{id}',     ['as' => 'postExaWpWorkOrder2',   'uses' => 'WorkPermit\WorkPermitRP2WorkOrderController@post']);

//工作許可證申請/執行單
Route::any('exa_wpworkorder3',           ['as' => 'exawpworkorderList3',   'uses' => 'WorkPermit\WorkPermitRP3AddMemberController@index']);
//Route::any('exa_wpworkorder3/{id}',      ['as' => 'exawpworkorderEdit3',   'uses' => 'WorkPermit\WorkPermitRP3AddMemberController@show']);
Route::any('new_wpworkorder3/{id}',  ['as' => 'wpworkorderCreate3', 'uses' => 'WorkPermit\WorkPermitRP3AddMemberController@show']);
Route::post('exa_wpworkorder3/{id}',     ['as' => 'postExaWpWorkOrder3',   'uses' => 'WorkPermit\WorkPermitRP3AddMemberController@post']);

//====================================報表=======================================//
//職員操作動作紀錄
Route::any('rept_activitylog',['as' => 'rept_activitylog',   'uses' => 'LogActionController@index']);
//廠區儀表板
Route::any('rept_doorinout_t',['as' => 'rept_doorinout_t',   'uses' => 'Report\ReptDoorInOutTodayController@index']);
//廠區儀表板
//Route::any('rept_doorinout_t1',['as' => 'rept_doorinout_t3',   'uses' => 'Report\ReptDoorInOutTodayController@index']);
//[不用登入網址參數]https://cpcdoor1.httcdoor.tk/rept_doorinout_t?local=ABCXDR
//廠區儀表板2
Route::any('rept_doorinout_t2',['as' => 'rept_doorinout_t2',   'uses' => 'Report\ReptDoorInOutListController@index']);
//查詢每日門口進出紀錄
Route::any('rept_doorinout_t3',['as' => 'rept_doorinout_t3',   'uses' => 'Report\ReptDoorInOutList2Controller@index']);
//查詢廠商進出紀錄
Route::any('rept_doorinout_t4',['as' => 'rept_doorinout_t4',   'uses' => 'Report\ReptDoorInoutList3Controller@index']);
//門禁進出報表
Route::any('rept_door_l1',['as' => 'rept_door_l1',   'uses' => 'Report\ReptDoor1Controller@index']);
Route::any('rept_door_l2',['as' => 'rept_door_l2',   'uses' => 'Report\ReptDoor2Controller@index']);
Route::any('rept_door_l3',['as' => 'rept_door_l3',   'uses' => 'Report\ReptDoor3Controller@index']);
Route::any('rept_door_l4',['as' => 'rept_door_l4',   'uses' => 'Report\ReptDoor4Controller@index']);
Route::any('door_edit',['as' => 'door_edit',   'uses' => 'Factory\DoorInOutEditController@index']);
Route::get('door_edit/{id}',['as' => 'door_edit_list',   'uses' => 'Factory\DoorInOutEditController@show']);
Route::post('door_edit/{id}',['as' => 'door_edit_post',   'uses' => 'Factory\DoorInOutEditController@post']);
//進出紀錄照片比對
Route::any('rept_door_img',['as' => 'rept_door_img',   'uses' => 'Report\ReptDoorShowImgController@index']);
//工作許可證儀表板
Route::any('rept_permit_t1',['as' => 'rept_permit_t1',   'uses' => 'Report\ReptPermit1Controller@index']);
Route::any('rept_permit_t2',['as' => 'rept_permit_t2',   'uses' => 'Report\ReptPermit2Controller@index']);
//工作許可證報表
Route::any('rept_permit_l1',['as' => 'rept_permit_l1',   'uses' => 'Report\ReptPermit3Controller@index']);
Route::any('rept_permit_l2',['as' => 'rept_permit_l2',   'uses' => 'Report\ReptPermit4Controller@index']);
//報表
Route::any('report_example',['as' => 'report_example',   'uses' => 'Report\ReptExampleController@index']);
Route::any('report_1',['as' => 'report_1',   'uses' => 'Report\Rept1Controller@index']);
Route::any('report_2',['as' => 'report_2',   'uses' => 'Report\Rept2Controller@index']);
Route::any('report_3',['as' => 'report_3',   'uses' => 'Report\Rept3Controller@index']);
Route::any('report_4',['as' => 'report_4',   'uses' => 'Report\Rept4Controller@index']);
Route::any('report_5',['as' => 'report_5',   'uses' => 'Report\Rept5Controller@index']);
Route::any('report_6',['as' => 'report_6',   'uses' => 'Report\Rept6Controller@index']);
Route::any('report_7',['as' => 'report_7',   'uses' => 'Report\Rept7Controller@index']);
Route::any('report_8',['as' => 'report_8',   'uses' => 'Report\Rept8Controller@index']);
Route::any('report_9',['as' => 'report_9',   'uses' => 'Report\Rept9Controller@index']);
Route::any('report_9a',['as' => 'report_9a',   'uses' => 'Report\Rept9Controller@index2']);
Route::any('report_10',['as' => 'report_10', 'uses' => 'Report\Rept10Controller@index']);
Route::any('report_10a',['as' => 'report_10a','uses' => 'Report\Rept10Controller@index2']);
Route::any('report_11',['as' => 'report_11',  'uses' => 'Report\Rept11Controller@index']);
Route::any('report_12',['as' => 'report_12',   'uses' => 'Report\Rept12Controller@index']);
Route::any('report_12a',['as' => 'report_12a', 'uses' => 'Report\Rept12Controller@index2']);
Route::any('report_13',['as' => 'report_13',   'uses' => 'Report\Rept13Controller@index']);
Route::any('report_14',['as' => 'report_14',   'uses' => 'Report\Rept14Controller@index']);
Route::any('report_15',['as' => 'report_15',   'uses' => 'Report\Rept15Controller@index']);
Route::any('report_16',['as' => 'report_16',   'uses' => 'Report\Rept16Controller@index']);
Route::any('report_17',['as' => 'report_17',   'uses' => 'Report\Rept17Controller@index']);
Route::any('report_18',['as' => 'report_18',   'uses' => 'Report\Rept18Controller@index']);
Route::any('report_19',['as' => 'report_19',   'uses' => 'Report\Rept19Controller@index']);
Route::any('report_20',['as' => 'report_20',   'uses' => 'Report\Rept20Controller@index']);
Route::any('report_21',['as' => 'report_21',   'uses' => 'Report\Rept21Controller@index']);
Route::any('report_22',['as' => 'report_22',   'uses' => 'Report\Rept22Controller@index']);
Route::any('report_23',['as' => 'report_23',   'uses' => 'Report\Rept23Controller@index']);
Route::any('report_24',['as' => 'report_24',   'uses' => 'Report\Rept24Controller@index']);
Route::any('report_25',['as' => 'report_25',   'uses' => 'Report\Rept25Controller@index']);
Route::any('report_26',['as' => 'report_26',   'uses' => 'Report\Rept26Controller@index']);
Route::any('report_27',['as' => 'report_27',   'uses' => 'Report\Rept27Controller@index']);
Route::any('report_28',['as' => 'report_28',   'uses' => 'Report\Rept28Controller@index']);
Route::any('report_29',['as' => 'report_29',   'uses' => 'Report\Rept29Controller@index']);
Route::any('report_30',['as' => 'report_30',   'uses' => 'Report\Rept30Controller@index']);
Route::any('report_31',['as' => 'report_31',   'uses' => 'Report\Rept31Controller@index']);
Route::any('report_32',['as' => 'report_32',   'uses' => 'Report\Rept32Controller@index']);
Route::any('report_33',['as' => 'report_33',   'uses' => 'Report\Rept33Controller@index']);
Route::any('report_34',['as' => 'report_34',   'uses' => 'Report\Rept34Controller@index']);
Route::any('report_35',['as' => 'report_35',   'uses' => 'Report\Rept35Controller@index']);
Route::any('report_36',['as' => 'report_36',   'uses' => 'Report\Rept36Controller@index']);
Route::any('report_37',['as' => 'report_37',   'uses' => 'Report\Rept37Controller@index']);
Route::any('report_38',['as' => 'report_38',   'uses' => 'Report\Rept38Controller@index']);
Route::any('report_39',['as' => 'report_39',   'uses' => 'Report\Rept39Controller@index']);
Route::any('report_40',['as' => 'report_40',   'uses' => 'Report\Rept40Controller@index']);
Route::any('report_41',['as' => 'report_41',   'uses' => 'Report\Rept41Controller@index']);
Route::any('report_42',['as' => 'report_42',   'uses' => 'Report\Rept42Controller@index']);
Route::any('report_43',['as' => 'report_43',   'uses' => 'Report\Rept43Controller@index']);
Route::any('report_44',['as' => 'report_44',   'uses' => 'Report\Rept44Controller@index']);
Route::any('report_45',['as' => 'report_45',   'uses' => 'Report\Rept45Controller@index']);
Route::any('report_46',['as' => 'report_46',   'uses' => 'Report\Rept46Controller@index']);
Route::any('report_47',['as' => 'report_47',   'uses' => 'Report\Rept47Controller@index']);
Route::any('report_48',['as' => 'report_48',   'uses' => 'Report\Rept48Controller@index']);
Route::any('report_49',['as' => 'report_49',   'uses' => 'Report\Rept49Controller@index']);
Route::any('report_50',['as' => 'report_50',   'uses' => 'Report\Rept50Controller@index']);
Route::any('report_51',['as' => 'report_51',   'uses' => 'Report\Rept51Controller@index']);
Route::any('report_52',['as' => 'report_52',   'uses' => 'Report\Rept52Controller@index']);
Route::any('report_53',['as' => 'report_53',   'uses' => 'Report\Rept53Controller@index']);
Route::any('report_54',['as' => 'report_54',   'uses' => 'Report\Rept54Controller@index']);
Route::any('report_55',['as' => 'report_55',   'uses' => 'Report\Rept55Controller@index']);
Route::any('report_56',['as' => 'report_56',   'uses' => 'Report\Rept56Controller@index']);
Route::any('report_57',['as' => 'report_57',   'uses' => 'Report\Rept57Controller@index']);
Route::any('report_58',['as' => 'report_58',   'uses' => 'Report\Rept58Controller@index']);
Route::any('report_59',['as' => 'report_59',   'uses' => 'Report\Rept59Controller@index']);
Route::any('report_62',['as' => 'report_62',   'uses' => 'Report\Rept62Controller@index']);
Route::any('report_62_rfid',['as' => 'report_62_rfid',   'uses' => 'Report\Rept62Controller@rfid']);
Route::any('report_62_license',['as' => 'report_62_license',   'uses' => 'Report\Rept62Controller@license']);
Route::any('report_62_traning',['as' => 'report_62_traning',   'uses' => 'Report\Rept62Controller@traning']);
Route::any('report_62_log_inout',['as' => 'report_62_log_inout',   'uses' => 'Report\Rept62Controller@log_inout']);
Route::any('report_62_violation',['as' => 'report_62_violation',   'uses' => 'Report\Rept62Controller@violation']);
//====================================報表=======================================//
//廠區儀表板
Route::any('findDoorRept1',['as' => 'findDoorRept1',   'uses' => 'Report\FindDoorController@findDoorInOutTodayFactory']);
Route::any('findDoorRept2',['as' => 'findDoorRept2',   'uses' => 'Report\FindDoorController@findDoorInOutToday']);
//Route::any('findDoorRept3',['as' => 'findDoorRept3',   'uses' => 'Report\FindDoorController@findDoorInoutTodaybyCar']);
Route::any('findDoorRept4',['as' => 'findDoorRept4',   'uses' => 'Report\FindDoorController@findFactoryTotalMenInOutAmt']);
Route::any('findDoorRept5',['as' => 'findDoorRept5',   'uses' => 'Report\FindDoorController@findFactoryTotalCarInOutAmt']);

Route::any('findPermitRept1',['as' => 'findPermitRept1',   'uses' => 'Report\FindReptPermitController@findPermitRept1']);
Route::any('findPermitRept2',['as' => 'findPermitRept2',   'uses' => 'Report\FindReptPermitController@findPermitRept2']);
Route::any('findPermitRept2a',['as' => 'findPermitRept2a',   'uses' => 'Report\FindReptPermitController@findPermitRept2A']);
Route::any('findPermitRept2b',['as' => 'findPermitRept2b',   'uses' => 'Report\FindReptPermitController@findPermitRept2B']);

//====================================附加檢點表=======================================//
Route::any('checkorder2a',['as' => 'checkorder2a',   'uses' => 'WorkPermit\PermitPrintCheckOrder2aController@index']);
Route::any('checkorder2b',['as' => 'checkorder2b',   'uses' => 'WorkPermit\PermitPrintCheckOrder2bController@index']);
Route::any('checkorder3a',['as' => 'checkorder3a',   'uses' => 'WorkPermit\PermitPrintCheckOrder3aController@index']);
Route::any('checkorder4a',['as' => 'checkorder4a',   'uses' => 'WorkPermit\PermitPrintCheckOrder4aController@index']);
Route::any('checkorder5a',['as' => 'checkorder5a',   'uses' => 'WorkPermit\PermitPrintCheckOrder5aController@index']);
Route::any('checkorder6a',['as' => 'checkorder6a',   'uses' => 'WorkPermit\PermitPrintCheckOrder6aController@index']);
Route::any('checkorder7a',['as' => 'checkorder7a',   'uses' => 'WorkPermit\PermitPrintCheckOrder7aController@index']);
Route::any('checkorder8a',['as' => 'checkorder8a',   'uses' => 'WorkPermit\PermitPrintCheckOrder8aController@index']);
Route::any('checkorder10a',['as' => 'checkorder10a',   'uses' => 'WorkPermit\PermitPrintCheckOrder10aController@index']);
Route::any('checkorder11a',['as' => 'checkorder11a',   'uses' => 'WorkPermit\PermitPrintCheckOrder11aController@index']);
Route::any('checkorder13a',['as' => 'checkorder13a',   'uses' => 'WorkPermit\PermitPrintCheckOrder13aController@index']);



//====================================異常檢查報表=======================================//

//工作許可證報表
Route::any('report_check1',['as' => 'report_check1',   'uses' => 'Report\ReptCheck1Controller@index']);
Route::any('report_check2',['as' => 'report_check2',   'uses' => 'Report\ReptCheck2Controller@index']);


//====================================大林中介系統=======================================//

//工作許可證報表
Route::any('showcpc1',['as' => 'showcpc1',   'uses' => 'Tmp\TmpProject201912Controller@index']);
Route::any('showcpc2/{id}',['as' => 'showcpc2',   'uses' => 'Tmp\TmpProjectMember201912Controller@index']);


//==================================APP=========================================//
//APP 選單維護
Route::any('app_menu',      ['as' => 'appmenuList',   'uses' => 'App\AppMenuController@index']);
Route::get('app_menu/{id}', ['as' => 'appmenuEdit',   'uses' => 'App\AppMenuController@show']);
Route::get('new_app_menu',  ['as' => 'appmenuCreate', 'uses' => 'App\AppMenuController@create']);
Route::post('app_menu/{id}',['as' => 'postAppMenu',   'uses' => 'App\AppMenuController@post']);
//APP 選單維護
Route::any('app_menu_select',      ['as' => 'appmenuselectList',   'uses' => 'App\AppMenuSelectController@index']);
Route::get('app_menu_select/{id}', ['as' => 'appmenuselectEdit',   'uses' => 'App\AppMenuSelectController@show']);
Route::get('new_app_menu_select',  ['as' => 'appmenuselectCreate', 'uses' => 'App\AppMenuSelectController@create']);
Route::post('app_menu_select/{id}',['as' => 'postAppMenuSelect',   'uses' => 'App\AppMenuSelectController@post']);
//[APP]選單群組
Route::any('appmenugroup',      ['as' => 'appmenugroupList',   'uses' => 'App\AppMenuGroupController@index']);
Route::get('appmenugroup/{id}', ['as' => 'appmenugroupEdit',   'uses' => 'App\AppMenuGroupController@show']);
Route::get('new_appmenugroup',  ['as' => 'appmenugroupCreate', 'uses' => 'App\AppMenuGroupController@create']);
Route::post('appmenugroup/{id}',['as' => 'postAppMenugroup',   'uses' => 'App\AppMenuGroupController@post']);
//[APP]選單群組權限設定
Route::get('appmenuauth/{id}', ['as' => 'appmenuauthList',   'uses' => 'App\AppMenuAuthController@index']);
Route::post('appmenuauth/{id}',['as' => 'postAppMenuAuth',   'uses' => 'App\AppMenuAuthController@post']);

//Index
Route::get('/', 'IndexController@index')->name('home');

