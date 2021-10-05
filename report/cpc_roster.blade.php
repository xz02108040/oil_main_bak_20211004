<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <style type="text/css">
        @page{

            margin: 0.5cm 0.6cm 0cm 0.6cm ;

        }


        .vertical-mode {
            writing-mode: tb-rl;
            -webkit-writing-mode: vertical-rl;
            writing-mode: vertical-rl;
        }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>承攬商工作人員名冊</title>
</head>

<body>
@for ($i = 1; $i <= $totalPage; $i++)
    <table width="1200" border="1" style="border:1px solid;font-family: 標楷體;border-bottom:0px;" align="center">
        <tr>
            <td  style="font-size:20pt;border:0px solid;">&nbsp;&nbsp;承&nbsp;攬&nbsp;商&nbsp;工&nbsp;作&nbsp;人&nbsp;員&nbsp;名&nbsp;冊<font style="font-size:10pt;">第&nbsp;&nbsp;{!!$i!!}&nbsp;&nbsp;頁共&nbsp;&nbsp;{!!$totalPage!!}&nbsp;&nbsp;頁</font></td>
            <td width="493" style="font-size:10pt;border:0px solid;vertical-align:bottom;">工程案號：{!!$project_no!!}</td>
        </tr>
        <tr>
            <td  style="font-size:10pt;border:0px solid;"></td>
            <td style="font-size:10pt;border:0px solid;">作業名稱：{!!$project_name!!}</td>
        </tr>
        <tr>
            <td  style="font-size:18pt;border:0px solid;border-bottom:0px;">承攬商：{!!$supply!!}</td>
            <td style="font-size:10pt;border:0px solid;border-bottom:0px;vertical-align:bottom;">工程期限：自&nbsp;{!!$sdate[0]!!}&nbsp;年&nbsp;{!!$sdate[1]!!}&nbsp;月&nbsp;{!!$sdate[2]!!}&nbsp;日起至&nbsp;{!!$edate[0]!!}&nbsp;年&nbsp;{!!$edate[1]!!}&nbsp;月&nbsp;{!!$edate[2]!!}&nbsp;日止</td>
        </tr>

    </table>

    <table width="1200" border="1" style="font-family: 標楷體;border:1px solid;border-collapse:collapse;border-bottom:1px;" align="center" solid>
        <tr>
            <td width="40" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;">編號</td>
            <td width="35" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;">工作類別</td>
            <td width="95" height="25"  valign="top" style="font-size: 12pt;border:1px solid;">姓名</td>
            <td width="20" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;">性別</td>
            <td width="95" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;">住址</td>
            <td colspan="3"  valign="top" style="font-size: 12pt;border:1px solid;">出生</td>
            <td width="56"  valign="top" style="font-size: 8pt;border:1px solid;"><b>證照字號</b></td>
            <td colspan="2" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;"><b>出入門別</b></td>
            <td width="100" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><b>聲明事項：本人同意建指紋檔、接受貴廠安全檢查。貴廠有權檢查本人是否攜帶違禁品，本人絕不拒絕<br>【請簽章&nbsp;&nbsp;】</b></td>
            <td width="65" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><b>勞工保險證號工、商、職字第&nbsp;&nbsp;號</b></td>
            <td width="75" rowspan="3"  valign="top" style="font-size: 10pt;border:1px solid;"><b>全民健保投保單位代（證）號</b></td>
            <td width="71" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><b>本公司、行號勞工，無勞工健康保護規則第二十條規定附表六之疾病及症狀，適合本承攬作業。</b></td>
            <td width="54" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><b>公司、行號負責人<br><br>確認簽章</b></td>
            <td width="80" rowspan="3"  valign="top" style="font-size: 12pt;border:1px solid;"><b>入廠講<br>習驗證</b></td>
            <td width="76" rowspan="3"  valign="top" style="font-size: 10pt;border:1px solid;">緊急聯絡電話<font style="font-size:8pt;"><br><b>（工安及工地負責人必填）</b></font></td>
            <td width="60" rowspan="3"  valign="top" style="font-size: 12pt;border: 1px solid; text-align: justify;">備註</td>
        </tr>
        <tr>
            <td height="25" style="font-size: 9pt;border:1px solid;"><b>身分證統一編號</b></td>
            <td width="20" rowspan="2"  valign="top" style="font-size: 12pt;border:1px solid;">年</td>
            <td width="20" rowspan="2"  valign="top" style="font-size: 12pt;border:1px solid;">月</td>
            <td width="20" rowspan="2"  valign="top" style="font-size: 12pt;border:1px solid;">日</td>
            <td rowspan="2"  valign="top" style="font-size: 6pt;border:1px solid;">本廠（政府機關）檢定</td>
        </tr>
        <tr>
            <td height="25" style="font-size: 12pt;border:1px solid;">磁卡號碼</td>
        </tr>
        @foreach ($memberAry[$i] as $val)
            <tr>
                <td width="40" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['id']!!}</td>
                <td width="35" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;"></td>
                <td width="95" height="32" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['name']!!}</td>
                <td width="20" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['sex']!!}</td>
                <td width="95" rowspan="3"   valign="top" style="font-size: 10pt;;border:1px solid;">{!!$val['address']!!}</td>
                <td width="20" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['birth'][0]!!}</td>
                <td width="20" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['birth'][1]!!}</td>
                <td width="20" rowspan="3" valign="top" style="font-size: 12pt;border:1px solid;">{!!$val['birth'][2]!!}</td>
                <td width="56" rowspan="3" valign="top" style="font-size: 10pt;border:1px solid;">&nbsp;</td>
                <td width="50" rowspan="3"  valign="top" style="font-size: 6pt;border:1px solid;">□大林廠<br>
                    □中林<br>
                    □二橋<br>
                    □高松<br>
                    □承商址<br>
                    □廠外<br>
                    □</td>
                <td width="50" rowspan="3"  valign="top" style="font-size: 6pt;border:1px solid;">□新北門<br>
                    □北門<br>
                    □西門<br>
                    □承商址<br>
                    □廠外<br>
                    □烏材林<br>
                    □觀音<br>
                    □半站<br>
                    □北站<br>
                    □</td>
                <td width="100" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><strong>聲明事項：本人同意建指紋檔、接受貴廠安全檢查。貴廠有權檢查本人是否 攜帶違禁品，本人絕不拒絕<br>【&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;】</strong></td>
                <td width="65" rowspan="3"  valign="top" style="font-size: 8pt;border:1px solid;"><b>工</b><br>{!!$val['identity1'][0]!!}<br><b>商</b><br>{!!$val['identity1'][1]!!}<br><b>職</b><br>{!!$val['identity1'][2]!!}
                </td>
                <td width="80" rowspan="3" style="font-size: 12pt;vertical-align:text-top;border:1px solid;">{!!$val['identity2']!!}</td>
                <td width="71" rowspan="3" valign="top" style="font-size: 8pt;border:1px solid;"><b>本公司、行號勞工，無勞工健康保護規則第二十條規定附表六之疾病及症狀，適合本承攬作業。</b></td>
                <td width="54" rowspan="3" style="font-size: 8pt;vertical-align:text-top;border:1px solid;">&nbsp;</td>
                <td width="62" rowspan="3" style="font-size: 12pt;vertical-align:text-top;border:1px solid;">{!!$val['cdate']!!}</td>
                <td width="89" rowspan="3" style="font-size: 12pt;vertical-align:text-top;border:1px solid;">{!!$val['kin']!!}</td>
                <td width="60" rowspan="3" style="font-size: 12pt;vertical-align:text-top;border:1px solid;">&nbsp;</td>
            </tr>
            <tr>
                <td height="32" valign="top" style="font-size: 12pt;border:1px solid;">&nbsp;{!!$val['bcid']!!}</td>
            </tr>
            <tr>
                <td height="32" valign="top" style="font-size: 12pt;border:1px solid;">&nbsp;{!!$val['rfid']!!}</td>
            </tr>
        @endforeach

    </table>
    <table width="1200" border="0" style="font-family: 標楷體;border:0px solid;border-collapse:collapse;" align="center">
        <tr>
            <td>監造部門名稱：</td>
            <td>監造人員：</td>
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
