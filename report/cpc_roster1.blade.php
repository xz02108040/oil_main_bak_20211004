<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>承攬商工作人員名冊</title>
</head>

<body>
@for ($i = 1; $i <= $totalPage; $i++)
<table width="1000" border="1" style="font-family: 標楷體;border-bottom:0px;" align="center">
  <tr>
    <td colspan="18" style="font-size:20pt;border:0px solid;">&nbsp;&nbsp;承&nbsp;攬&nbsp;商&nbsp;工&nbsp;作&nbsp;人&nbsp;員&nbsp;名&nbsp;冊<font style="font-size:10pt;">第&nbsp;{!!$i!!}&nbsp;頁共&nbsp;{!!$totalPage!!}&nbsp;頁</font></td>
    <td width="493" style="font-size:10pt;border:0px solid;vertical-align:bottom;">工程案號：{!!$project_no!!}</td>
  </tr>
  <tr>
    <td colspan="18" style="font-size:10pt;border:0px solid;"></td>
    <td style="font-size:10pt;border:0px solid;">作業名稱：{!!$project_name!!}</td>
  </tr>
  <tr>
    <td colspan="18" style="font-size:18pt;border:0px solid;border-bottom:0px;">承攬商：{!!$supply!!}</td>
    <td style="font-size:10pt;border:0px solid;border-bottom:0px;vertical-align:bottom;">工程期限：自&nbsp;{!!$sdate[0]!!}年&nbsp;{!!$sdate[1]!!}月&nbsp;{!!$sdate[2]!!}日起至&nbsp;{!!$edate[0]!!}年&nbsp;{!!$edate[1]!!}月&nbsp;{!!$edate[2]!!}日止</td>
  </tr>
  
</table>

<table width="1000" border="1" style="font-family: 標楷體;border:1px solid;border-collapse:collapse;border-bottom:0px;" align="center" solid>
      <tr>
    <td width="20" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">編號</td>
    <td width="32" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">工作類別</td>
    <td width="91" height="32" style="font-size: 12pt;vertical-align:text-top;">姓名</td>
    <td width="16" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">性別</td>
    <td width="81" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">住址</td>
    <td colspan="3" style="font-size: 12pt;vertical-align:text-top;">出生</td>
    <td width="26" style="font-size: 8pt;"><b>證照字號</b></td>
    <td colspan="2" rowspan="3" style="font-size: 12pt;vertical-align:text-top;"><b>出入門別</b></td>
    <td width="70" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>聲明事項：本人同意建指紋檔、接受貴廠安全檢查。貴廠有權檢查本人是否攜帶違禁品，本人絕不拒絕<br>【請簽章&nbsp;&nbsp;】</b></td>
    <td width="66" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>勞工保險證號工、商、職字第&nbsp;&nbsp;號</b></td>
    <td width="48" rowspan="3" style="font-size: 10pt;vertical-align:text-top;"><b>全民健保投保單位代（證）號</b></td>
    <td width="69" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>本公司、行號勞工，無勞工健康保護規則第二十條規定附表六之疾病及症狀，適合本承攬作業。</b></td>
    <td width="54" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>公司、行號負責人<br><br>確認簽章</b></td>
    <td width="54" rowspan="3" style="font-size: 12pt;vertical-align:text-top;"><b>入廠講習驗證</b></td>
    <td width="84" rowspan="3" style="font-size: 10pt;vertical-align:text-top;">緊急聯絡電話<font style="font-size:8pt;"><br><b>（工安及工地負責人必填）</b></font></td>
    <td width="57" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">備註</td>
  </tr>
  <tr>
    <td height="32" style="font-size: 9pt;"><b>身分證統一編號</b></td>
    <td width="16" rowspan="2" style="font-size: 12pt;vertical-align:text-top;">年</td>
    <td width="16" rowspan="2" style="font-size: 12pt;vertical-align:text-top;">月</td>
    <td width="16" rowspan="2" style="font-size: 12pt;vertical-align:text-top;">日</td>
    <td rowspan="2" style="font-size: 6pt;vertical-align:text-top;">本廠（政府機關）檢定</td>
  </tr>
  <tr>
    <td height="32" style="font-size: 12pt;">磁卡號碼</td>
  </tr>
  <tr>
    <td width="20" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][1]['no']!!}</td>
      <td width="32" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][1]['cpc_tag']!!}</td>
      <td width="91" height="37" style="font-size: 12pt;">{!!$memberAry[$i][1]['name']!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][1]['sex']!!}</td>
      <td width="81" rowspan="3" style="font-size: 10pt;vertical-align:text-top;text-align: right;">{!!$memberAry[$i][1]['address']!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][1]['birth'][0]!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][1]['birth'][1]!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][1]['birth'][2]!!}</td>
      <td width="26" rowspan="3" style="font-size: 8pt;">&nbsp;</td>
    <td width="33" rowspan="3" style="font-size: 6pt;vertical-align:text-top;"><p>□大林廠<br>
      □中林<br>
      □二橋<br>
      □高松<br>
      □承商址<br>
      □廠外<br>
      □</td>
    <td width="33" rowspan="3" style="font-size: 6pt;vertical-align:text-top;"><p>□新北門<br>
      □北門<br>
      □西門<br>
      □承商址<br>
      □廠外<br>
      □烏材林<br>
      □觀音<br>
      □半站<br>
      □北站<br> 
□</td>
    <td width="70" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><strong>聲明事項：本人同意建指紋檔、接受貴廠安全檢查。貴廠有權檢查本人是否 攜帶違禁品，本人絕不拒絕<br>【&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;】</strong></td>
    <td width="66" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>工</b><br><br><b>商</b><br><br><b>職</b>
</td>
    <td width="48" rowspan="3" style="font-size: 8pt;vertical-align:text-top;">&nbsp;</td>
    <td width="69" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>本公司、行號勞工，無勞工健康保護規則第二十條規定附表六之疾病及症狀，適合本承攬作業。</b></td>
    <td width="54" rowspan="3" style="font-size: 8pt;vertical-align:text-top;">&nbsp;</td>
    <td width="54" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">&nbsp;</td>
    <td width="84" rowspan="3" style="font-size: 10pt;vertical-align:text-top;">&nbsp;{!!$memberAry[$i][1]['kin']!!}</td>
    <td width="57" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">&nbsp;</td>
  </tr>
  <tr>
    <td height="37" style="font-size: 9pt;">&nbsp;{!!$memberAry[$i][1]['bc_id']!!}</td>
  </tr>
  <tr>
    <td height="37" style="font-size: 12pt;">&nbsp;{!!$memberAry[$i][1]['rfid']!!}</td>
  </tr>
  <tr>
      <td width="20" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][2]['no']!!}</td>
      <td width="32" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][2]['cpc_tag']!!}</td>
      <td width="91" height="37" style="font-size: 12pt;">{!!$memberAry[$i][2]['name']!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">{!!$memberAry[$i][2]['sex']!!}</td>
      <td width="81" rowspan="3" style="font-size: 10pt;vertical-align:text-top;text-align: right;">{!!$memberAry[$i][2]['address']!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][2]['birth'][0]!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][2]['birth'][1]!!}</td>
      <td width="16" rowspan="3" style="font-size: 12pt;">{!!$memberAry[$i][2]['birth'][2]!!}</td>
    <td width="26" rowspan="3" style="font-size: 8pt;">&nbsp;</td>
    <td width="33" rowspan="3" style="font-size: 6pt;vertical-align:text-top;"><p>□大林廠<br>
      □中林<br>
      □二橋<br>
      □高松<br>
      □承商址<br>
      □廠外<br>
      □</td>
    <td width="33" rowspan="3" style="font-size: 6pt;vertical-align:text-top;"><p>□新北門<br>
      □北門<br>
      □西門<br>
      □承商址<br>
      □廠外<br>
      □烏材林<br>
      □觀音<br>
      □半站<br>
      □北站<br> 
□</td>
    <td width="70" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><strong>聲明事項：本人同意建指紋檔、接受貴廠安全檢查。貴廠有權檢查本人是否 攜帶違禁品，本人絕不拒絕<br>【&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;】</strong></td>
    <td width="66" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>工</b><br><br><b>商</b><br><br><b>職</b>
</td>
    <td width="48" rowspan="3" style="font-size: 8pt;vertical-align:text-top;">&nbsp;</td>
    <td width="69" rowspan="3" style="font-size: 8pt;vertical-align:text-top;"><b>本公司、行號勞工，無勞工健康保護規則第二十條規定附表六之疾病及症狀，適合本承攬作業。</b></td>
    <td width="54" rowspan="3" style="font-size: 8pt;vertical-align:text-top;">&nbsp;</td>
    <td width="54" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">&nbsp;</td>
    <td width="84" rowspan="3" style="font-size: 10pt;vertical-align:text-top;">&nbsp;{!!$memberAry[$i][2]['kin']!!}</td>
    <td width="57" rowspan="3" style="font-size: 12pt;vertical-align:text-top;">&nbsp;</td>
  </tr>
  <tr>
      <td height="37" style="font-size: 9pt;">&nbsp;{!!$memberAry[$i][2]['bc_id']!!}</td>
  </tr>
    <tr>
        <td height="37" style="font-size: 12pt;">&nbsp;{!!$memberAry[$i][2]['rfid']!!}</td>
  </tr>
</table>
<table width="1000" border="0" style="font-family: 標楷體;border:0px solid;border-collapse:collapse;" align="center">
  <tr>
    <td>監造部門名稱：{!!$charge_dept!!}</td>
    <td>監造人員：{!!$charge_user!!}</td>
    <td>監造主管：</td>
    <td>工安衛生課：</td>
    <td>5542-PRO-02</td>
  </tr>
  
  <tr>
    <td colspan="5"><strong>備註：1.從西門進出、承商址施工或廠外施工者不須辦理出入証。2.承攬商工作人員名冊一式四份正本，（大林廠須六份正本）</strong></td>
  </tr>
  <tr>
    <td colspan="5"><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;監造部門及工安課各一份，採購二組二份。<font style="font-size:10pt">（傳遞順序：監造部門→工安課→監造部門→併開工通知書送採購二組）</font></strong></td>
  </tr>
</table>
<p style='page-break-after:always'> </p>
@endfor
</body>
</html>
