<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>大林煉油廠承攬商入場安全衛生講習報名表</title>
</head>

<body>

@foreach ($pageAry as $val)
    <table width="700" style="font-family: 標楷體;" border="0" align="center">
        <tbody>
        <tr>
            <td colspan="2" style="text-align: center; font-size: 16pt;"><b>大林煉油廠承攬商入廠安全衛生講習報名表</b>
            </td>
        </tr>
        <tr>
            <td width="348" style="font-size: 12pt;">◎工程案號：{!!$val['project_no']!!}</td>
            <td width="348" style="font-size: 12pt;">◎本廠監造人員蓋(簽)章：<u> 　 　    　　 　 </u></td>
        </tr>
        <tr>
            <td style="font-size: 12pt;">※未經本廠監造人員蓋（簽）章者，不接受報名</td>
            <td style="font-size: 12pt;">※請以正楷填寫（◎講習相關欄請勿填寫）</td>
        </tr>
        <tr>
            <td style="font-size: 12pt;">※成績單隔週第一個工作日拿</td>
            <td style="font-size: 12pt;">填表日期：&nbsp;{!!$val['member'][0]['traning_date_y']!!}&nbsp;年&nbsp;{!!$val['member'][0]['traning_date_m']!!}&nbsp;月&nbsp;{!!$val['member'][0]['traning_date_d']!!}&nbsp;日</td>
        </tr>
        <tr>
            <td colspan="7">
                <table width="700" border="1" style="border:1px solid;border-collapse:collapse;border-bottom:0px;" solid>
                    <tr>
                        <td width="91" rowspan="2"  style="text-align: center;border:1px solid;">公司名稱</td>
                        <td colspan="2" rowspan="2"  style="text-align: center;border:1px solid;">{!!$val['supply_name']!!}</td>
                        <td width="92" rowspan="2"  style="text-align: center;border:1px solid;">統一編號</td>
                        <td width="135" rowspan="2"  style="text-align: center;border:1px solid;">{!!$val['supply_no']!!}</td>
                        <td width="61"  style="text-align: center;border:1px solid;">電話</td>
                        <td width="102"  style="text-align: center;border:1px solid;">{!!$val['supply_tel']!!}</td>
                    </tr>
                    <tr>
                        <td  style="text-align: center;border:1px solid;">傳真</td>
                        <td  style="text-align: center;border:1px solid;">{!!$val['supply_fax']!!}</td>
                    </tr>
                    <tr>
                        <td  style="text-align: center;border:1px solid;border-bottom:0px;">負責人</td>
                        <td width="134"  style="text-align: center;border:1px solid;border-bottom:0px;">{!!$val['supply_boss']!!}</td>
                        <td width="39"  style="text-align: center;border:1px solid;border-bottom:0px;">地址</td>
                        <td colspan="4"  style="text-align: center;border:1px solid;border-bottom:0px;">{!!$val['supply_addr']!!}</td>
                    </tr>
                </table>

                <table width="700" border="1" style="border:1px solid;border-collapse:collapse;" solid>
                    <tr>
                        <td width="23" rowspan="2"  style="text-align: center;border:1px solid;">項次</td>
                        <td width="145" rowspan="2"  style="text-align: center;border:1px solid;">姓名</td>
                        <td colspan="10" rowspan="2"  style="text-align: center;border:1px solid;">身分證字號</td>
                        <td colspan="3"  style="text-align: center;border:1px solid;">◎講習相關欄(免填)</td>
                        <td width="102" rowspan="2"  style="text-align: center;border:1px solid;">簽名</td>
                    </tr>
                    <tr>
                        <td width="80"  style="text-align: center;border:1px solid;">上課地點</td>
                        <td width="80"  style="text-align: center;border:1px solid;">上課時間</td>
                        <td width="70"  style="text-align: center;border:1px solid;">成績</td>
                    </tr>
                    @foreach ($val['member'] as $val2)
                        <tr>
                            <td height="30"  style="text-align: center;border:1px solid;">{!!$val2['no']!!}</td>
                            <td  style="text-align: center;border:1px solid;">{!!$val2['name']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid1']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid2']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid3']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid4']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid5']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid6']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid7']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid8']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid9']!!}</td>
                            <td width="10"  style="text-align: center;border:1px solid;">{!!$val2['bcid10']!!}</td>
                            <td  style="text-align: center;border:1px solid;">&nbsp;</td>
                            <td  style="text-align: center;border:1px solid;">{!!$val2['traning_time']!!}</td>
                            <td  style="text-align: center;border:1px solid;">&nbsp;</td>
                            <td  style="text-align: center;border:1px solid;">&nbsp;</td>
                        </tr>
                    @endforeach
                </table>

            </td>
        </tr>

        <tr>
            <td colspan="2" style="font-size: 12pt;"><b>承攬商入廠安全衛生講習須知</b></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">1.講習對象：凡進入本廠之承攬商工作人員及使用長期公務通行證者。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">2.講習時間：每周五上午開壹班，每次上課4小時(含測驗)。請自行帶筆。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">3.經測驗成績80分以上合格者，始可申請出入證進本廠工作。凡測驗成績未滿80分或未接受『承攬商入廠安全衛生講習』者，本廠將不受理廠商申請「承攬商工作人員出入證」。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">4.報名：講習一週前承攬商自行填寫承攬商入廠安全衛生講習報名表，送工安衛生課報名。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">&nbsp;&nbsp;(1)索取報名表地點：工安衛生課、承攬商辦證室。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">&nbsp;&nbsp;(2)個人及上課現場不受理辦理。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">&nbsp;&nbsp;(3)報名時須附勞卡影本。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">&nbsp;&nbsp;(4)講習費用：免費。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">&nbsp;&nbsp;(5)有效期限：講習測驗合格後有效期限為壹年。			        </td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">5.欲進入本廠工作之承攬商工作人員，請承攬商提早為其報名參加『承攬商入廠安全衛生講習』，以免延誤施工。</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;">6.本公司高雄煉油廠、桃園煉油廠、林園石化廠及興工處等舉辦之入廠安全衛生講習成績合格且在有效期限內者，本廠認可有效。<font style="font-size:14pt;"><u>上課地點在隔音大樓工安教室</u></font></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12pt;text-align:right;">570-OSM-0B</td>
        </tr>
        </tbody>
    </table>
    <p style='page-break-after:always'> </p>

@endforeach


</body>
</html>
