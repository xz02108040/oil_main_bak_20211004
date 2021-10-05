<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <style type="text/css">
        @page{
            margin: 1cm 1cm 0cm 1cm ;
        }
        body{font-family: 標楷體,sans-serif;}
        .chk_img {
            max-width:95%;
            max-height:450px;
            height:auto;
        }
        .time_at {
            font-size: 10pt;
        }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>大林煉油廠工作許可證{!!$permit_no!!}</title>

</head>

<body>

<table width="1000" style="font-family: 標楷體;" border="0" align="center">
    <tbody>
    <tr>
        <td  colspan="3">
        <span style="float:right"><img src="{!!$qrcode!!}" width="80" height="80" border="0"></span>
        </td>
    </tr>
    <tr>
        <td height="10" colspan="2"  style="text-align: center; font-size: 14pt;">
            大林煉油廠工作許可證
        </td>
    </tr>
    <tr>

        <td height="10" colspan="3" style="text-align: right; font-size: 11pt;">最後修改時間：<u >{!!$last_updated_at!!}</u></td>
    </tr>
    <tr>

        <td height="10" colspan="3" style="text-align: right; font-size: 11pt;">本證編號：<u >{!!$permit_no!!}</u></td>
    </tr>
    <tr>
        <td height="10"  colspan="3" style="font-size: 11pt;">申請時間：<u>{!!$apply_date!!}</u></td>

    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">預計工作時間：<u>{!!$work_date_Y!!}年{!!$work_date_M!!}月{!!$work_date_D!!}日 &nbsp;&nbsp;&nbsp;上午&nbsp;&nbsp;&nbsp;&nbsp;{!!$work_date_T1!!}&nbsp;&nbsp;&nbsp;&nbsp;至上午&nbsp;&nbsp;&nbsp;&nbsp;{!!$work_date_T2!!}&nbsp;&nbsp;&nbsp;&nbsp;/下午&nbsp;&nbsp;&nbsp;&nbsp;{!!$work_date_T3!!}&nbsp;&nbsp;&nbsp;&nbsp;至下午&nbsp;&nbsp;&nbsp;&nbsp;{!!$work_date_T4!!}&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">工程作業分級：{!!$permit_danger_a!!}A級(高危險作業)&nbsp;{!!$permit_danger_b!!}B級(危險作業)&nbsp;{!!$permit_danger_c!!}C級(低危險作業)
        </td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(1)施工部門：<u>{!!$dept_name6!!}</u>&nbsp;&nbsp;&nbsp;&nbsp;轄區部門：<u>{!!$dept_name1!!}</u>&nbsp;&nbsp;&nbsp;&nbsp;監造部門：<u>{!!$dept_name2!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">&nbsp;&nbsp;&nbsp;承攬商：<u>{!!$supply!!}</u>&nbsp;工作人員人數：<u>{!!$supply_men!!}</u>&nbsp;車輛車號：<u>{!!$supply_car!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(2)施工地點：<u>{!!$work_place!!}</u>工程案號：<u>{!!$project_no!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(3)工作內容：<u>{!!$work_memo!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(4)許可工作項目：{!!$chk_isOvertime!!}預計延時工作</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">&nbsp;&nbsp;&nbsp;非動火：{!!$chk_workitem[1]!!}高處作業&nbsp;{!!$chk_workitem[2]!!}油漆作業&nbsp;{!!$chk_workitem[3]!!}油氣管線拆修&nbsp;{!!$chk_workitem[4]!!}電器檢修&nbsp;{!!$chk_workitem[5]!!}海上作業&nbsp;{!!$chk_workitem[6]!!}開挖作業&nbsp;{!!$chk_workitem[7]!!}保溫保冷作業<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!!$chk_workitem[8]!!}機動車輛、引擎進入廠區內部&nbsp;{!!$chk_workitem[9]!!}其他：<u>{!!$chk_workitem[10]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">&nbsp;&nbsp;&nbsp;動火：{!!$chk_workitem[20]!!}砂輪機&nbsp;{!!$chk_workitem[21]!!}電、氣焊&nbsp;{!!$chk_workitem[22]!!}發電機發電&nbsp;{!!$chk_workitem[23]!!}噴砂、鋼絲刷除銹&nbsp;{!!$chk_workitem[24]!!}切割金屬&nbsp;{!!$chk_workitem[25]!!}混泥土破碎機&nbsp;{!!$chk_workitem[26]!!}其他：<u>{!!$chk_workitem[27]!!}</u><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;□機動車輛、引擎進入廠區內部</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">&nbsp;&nbsp;&nbsp;局限空間：{!!$chk_workitem[30]!!}坑洞、方舟、涵洞、油水池等內部作業&nbsp;{!!$chk_workitem[31]!!}煉儲設備內部&nbsp;{!!$chk_workitem[32]!!}其他：<u>{!!$chk_workitem[33]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(5)附加作業檢點：<u>{!!$permit_check!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(6)管線或設備之內容物：<u>{!!$chk_workline[11]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="2" style="font-size: 11pt;">&nbsp;&nbsp;&nbsp;{!!$chk_workline[2]!!}可燃性氣體(天然氣、氫氣、燃料氣等)&nbsp;{!!$chk_workline[3]!!}易燃液體(汽油、煤油、正己烷等)&nbsp;{!!$chk_workline[4]!!}有害性氣體(硫化氫、氨氣、氮氣等)<br>
            &nbsp;&nbsp;&nbsp;{!!$chk_workline[5]!!}化學藥劑(抗蝕劑、抗污劑等)&nbsp;{!!$chk_workline[6]!!}酸鹼(硫酸、氫氧化鈉等)&nbsp;{!!$chk_workline[7]!!}熱水、蒸氣&nbsp;{!!$chk_workline[8]!!}空氣(通風完善)&nbsp;{!!$chk_workline[1]!!}其他</td>
    </tr>
    <tr>
        <td width="487" height="10" style="font-size: 11pt;border-bottom:3px double">(7)下述簽發前安全檢點((施工/環境)有關項目打V，無關者劃=&nbsp;&nbsp;</td>
        <td width="209" height="10" colspan="2"style="font-size: 11pt;text-align: right;border-bottom:3px double">監造人員：<u>{!! $sign_url1 !!}(簽名)</u></td>

    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(8)施工安全檢點(發包工程由承攬商負責/廠方施工由施工部門負責，監造部門監督)</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;" >{!!$chk_supply_topic[1]!!}每一施工現場十公尺內應備有20型以上手提滅火器&nbsp;{!!$chk_supply_topic[24]!!}已備有校正合格四用氣體偵測器，<font color="#FF0000">進行監測並</font>每小時記錄備查</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[7]!!}二公尺以上無標準平台之高架作業已備妥&nbsp;{!!$chk_supply_topic[2]!!}標準施工架&nbsp;{!!$chk_supply_topic[3]!!}安全網&nbsp;{!!$chk_supply_topic[4]!!}安全帶&nbsp;{!!$chk_supply_topic[5]!!}其他<u>{!!$chk_supply_topic[6]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[10]!!}危險場所機具使用：{!!$chk_supply_topic[8]!!}防爆電氣設備&nbsp;{!!$chk_supply_topic[9]!!}安全工具</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[23]!!}已備有個人防護具：{!!$chk_supply_topic[11]!!}防塵口罩&nbsp;{!!$chk_supply_topic[12]!!}防毒口罩&nbsp;{!!$chk_supply_topic[13]!!}防毒面具&nbsp;{!!$chk_supply_topic[14]!!}自給式空氣呼吸器&nbsp;{!!$chk_supply_topic[15]!!}輸氣管式空氣呼吸器&nbsp;{!!$chk_supply_topic[16]!!}氧氣救生器<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!!$chk_supply_topic[17]!!}防護衣褲&nbsp;{!!$chk_supply_topic[18]!!}防酸鹼手套&nbsp;{!!$chk_supply_topic[19]!!}絕緣手套&nbsp;{!!$chk_supply_topic[20]!!}防護眼罩&nbsp;{!!$chk_supply_topic[21]!!}救生索&nbsp;{!!$chk_supply_topic[22]!!}SOS自動警報器(局限空間作業時)</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[70]!!}附加作業檢點表：{!!$chk_supply_topic[60]!!}吊掛&nbsp;{!!$chk_supply_topic[61]!!}局限&nbsp;{!!$chk_supply_topic[62]!!}施工架&nbsp;{!!$chk_supply_topic[63]!!}盲板&nbsp;{!!$chk_supply_topic[64]!!}開挖&nbsp;{!!$chk_supply_topic[65]!!}輻射&nbsp;{!!$chk_supply_topic[66]!!}閉鎖&nbsp;{!!$chk_supply_topic[67]!!}其他&nbsp;<u>{!!$chk_supply_topic[68]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic['32a']!!}看火者：<u>{!!$chk_supply_topic[30]!!}</u>&nbsp;{!!$chk_supply_topic['32b']!!}人孔監視者：<u>{!!$chk_supply_topic[31]!!}</u>&nbsp;{!!$chk_supply_topic[35]!!}施工人員(人數、姓名或另附名冊)：<u>{!!$chk_supply_topic['34a']!!}<br />
            </u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;"><u>{!!$chk_supply_topic['34b']!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[37]!!}缺氧作業主管：<u>{!!$chk_supply_topic[36]!!}</u>{!!$chk_supply_topic[39]!!}施工架組配作業主管：<u>{!!$chk_supply_topic[38]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[41]!!}起重/{!!$chk_supply_topic[43]!!}吊掛人員：<u>{!!$chk_supply_topic[40]!!}/</u><u>{!!$chk_supply_topic[42]!!}</u>{!!$chk_supply_topic[45]!!}有機溶劑作業主管：<u>{!!$chk_supply_topic[44]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_supply_topic[50]!!}廠工帶班者：<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(簽名)</u><span style="font-size: 11pt;">&nbsp;</span><u>{!!$chk_supply_topic[47]!!}</u>其他作業主管：{!!$chk_supply_topic[46]!!}</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;border-bottom:3px double"> {!!$chk_supply_topic[51]!!}承攬商：職安衛人員<u>{!! $sign_url3 !!}(簽名)</u><span style="font-size: 11pt;">&nbsp;</span>電話：<u>{!!$chk_supply_topic[52]!!}</u><span style="font-size: 11pt;">&nbsp;</span>工地負責人：<u>{!! $sign_url4 !!}(簽名)</u><span style="font-size: 11pt;">&nbsp;</span>電話：<u>{!!$chk_supply_topic[53]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(9)環境安全檢點(轄區)</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_emp_topic[1]!!}設備或管線原存物質<u>{!!$chk_emp_topic[2]!!}</u>&nbsp;{!!$chk_emp_topic[3]!!}設備、管線已釋壓並吹驅乾淨或清洗</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_emp_topic[4]!!}確認進出口已&nbsp;{!!$chk_emp_topic[5]!!}關斷&nbsp;{!!$chk_emp_topic[6]!!}加盲&nbsp;{!!$chk_emp_topic[7]!!}掛牌&nbsp;{!!$chk_emp_topic[8]!!}盲板圖標示已掛於現場&nbsp;{!!$chk_emp_topic[9]!!}已備妥通風設備&nbsp;{!!$chk_emp_topic[10]!!}電源已隔離、加鎖及掛牌標示</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_emp_topic[11]!!}施工現場十公尺內或下方之暗溝口、方井、電纜溝口已堵塞並密封&nbsp;{!!$chk_emp_topic[12]!!}地面已無遺浮油、雜物及可燃物,確已做好安全處理</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_emp_topic[19]!!}施工現場十公尺內已備妥&nbsp;{!!$chk_emp_topic[13]!!}手提滅火器&nbsp;{!!$chk_emp_topic[14]!!}輪架式滅火車&nbsp;{!!$chk_emp_topic[15]!!}高壓噴槍&nbsp;{!!$chk_emp_topic[16]!!}消防水帶接妥消防栓&nbsp;{!!$chk_emp_topic[17]!!}其它<u>{!!$chk_emp_topic[18]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!!$chk_emp_topic[23]!!}緊急事故時,承攬商疏散到指定&nbsp;地點:<u>{!!$chk_emp_topic[20]!!}</u> 聯絡人:<u>{!!$chk_emp_topic[21]!!}</u> 電話:<u>{!!$chk_emp_topic[22]!!}</u></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">作業前環境檢測:</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">
            <table width="675" border="1" align="center" style="border:1px solid;border-collapse:collapse;" solid>
                <tr>
                    <td width="57" style="text-align: center;border:1px solid;">檢測項目</td>
                    <td width="114" style="text-align: center;border:1px solid;">可燃性氣體(%LEL)</td>
                    <td width="60" style="text-align: center;border:1px solid;">氧氣(%)</td>
                    <td width="94" style="text-align: center;border:1px solid;">一氧化碳(ppm)</td>
                    <td width="82" style="text-align: center;border:1px solid;">硫化氣(ppm)</td>
                    <td width="53" style="text-align: center;border:1px solid;">其他</td>
                    <td width="81" rowspan="2" style="text-align: center;border:1px solid;">附註</td>
                    <td width="81" rowspan="2" style="text-align: center;border:1px solid;">檢測時間</td>
                    <td width="82" rowspan="2" style="text-align: center;border:1px solid;">簽名</td>
                </tr>
                <tr>
                    <td style="text-align: center;border:1px solid;">安全值</td>
                    <td style="text-align: center;border:1px solid;">&lt;20%</td>
                    <td style="text-align: center;border:1px solid;">18-21%</td>
                    <td style="text-align: center;border:1px solid;">&lt;35ppm </td>
                    <td style="text-align: center;border:1px solid;">&lt;10ppm</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[105]!!}</td>
                </tr>
                <tr>
                    <td style="text-align: center;border:1px solid;">施工人員</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[101]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[102]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[103]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[104]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[106]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[109]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[100]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_supply_topic[108]!!}</td>
                </tr>
                <tr>
                    <td style="text-align: center;border:1px solid;">轄區人員</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[101]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[102]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[103]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[104]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[106]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[109]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[100]!!}</td>
                    <td style="text-align: center;border:1px solid;">{!!$chk_emp_topic[108]!!}</td>
                </tr>
            </table></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">轄區:檢點者:<u>{!! $sign_url5 !!}</u><span style="text-align: right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;連繫者：{!!$chk_emp_topic[30]!!}專任<span style="font-size: 11pt;"> </span>{!!$chk_emp_topic[31]!!}機動<u>{!! $sign_url6 !!}</u><span style="font-size: 11pt;">(簽名) </span>電話<u>{!!$chk_emp_topic[32]!!}</u></span></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;"><span style="font-size: 11pt;">{!! $chk_emp_delay_Topic !!}延時工作時間：<u>&nbsp;{!! $eta_etime1_a !!}&nbsp;時</u><u>&nbsp;{!! $eta_etime1_b !!}&nbsp;分</u>&nbsp;至<u>&nbsp;{!! $eta_etime2_a !!}時&nbsp;{!! $eta_etime2_b !!}&nbsp;分</u>&nbsp;&nbsp;&nbsp;&nbsp;{!! $chk_delay_charge1_topic !!}監造：<u>&nbsp;&nbsp;&nbsp;&nbsp;{!! $chk_delay_charge1 !!}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><span style="font-size: 11pt;">&nbsp;</span>{!! $chk_delay_charge3_topic !!}施工：<u>&nbsp;&nbsp;&nbsp;&nbsp;{!! $chk_delay_charge3 !!}&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 11pt;">&nbsp;&nbsp;</span></u>&nbsp;{!! $chk_delay_charge2_topic !!}轄區：<u>&nbsp;&nbsp;&nbsp;&nbsp;{!! $chk_delay_charge2 !!}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></span></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">(10)A級作業及關鍵性設備施工前已實施現場會勘<span style="font-size: 11pt;border-bottom:3px">&nbsp;</span>{!! $chk_emp_topic[34] !!}已檢附作業前現場會勘紀錄表(參考700-ISM-09<span style="font-size: 11pt;border-bottom:3px">&nbsp;</span>附件6.8)</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;"><font color="#FF0000">(11)工安叮嚀與重要提醒事項</font></td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">{!! $chk_emp_topic[33] !!}</td>
    </tr>
    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;text-align: right;border-bottom:2px solid">{!!$chk_emp_topic[38]!!}監造：<u>{!! $sign_url8 !!}</u><span style="font-size: 11pt;">&nbsp;</span>{!!$chk_emp_topic[39]!!}施工：<u>{!! $sign_url9 !!}</u><span style="font-size: 11pt;">&nbsp;</span>{!!$chk_emp_topic[40]!!}轄區：<u>{!! $sign_url10 !!}</u></td>
    </tr>

    <tr>
        <td height="10" colspan="3" style="font-size: 11pt;">
            <table width="1000" align="center" style="border:1px solid;border-collapse:collapse;border-bottom:0px;" solid>
                <tr>
                    <td width="260" style="border:1px solid;border-bottom:0px;">會簽單位/主簽人：<u>{!! $dept_name4 !!}/{!! $sign_url14 !!}</u></td>
                    <td width="440" style="border:1px solid;border-bottom:0px;">回簽：{!!$chk_emp_topic[52]!!}環境已整理及作好防護措施&nbsp;&nbsp;&nbsp;承商(或廠工)：{!! $sign_url18 !!}</td>
                </tr>
            </table>
            <table width="1000" align="center" style="border:1px solid;border-collapse:collapse;border-bottom:0px;" solid>
                <tr>
                    <td width="520" style="border:1px solid;border-bottom:0px;">轄區複檢者/課級主管(工場長)/組級主管(經理)：<u>{!! $sign_url15 !!}/{!! $sign_url16 !!}/{!! $sign_url17 !!}</u></td>
                    <td width="180" style="border:1px solid;border-bottom:0px;">轄區：{!! $sign_url19 !!}</td>
                </tr>
            </table>
            <table width="1000" align="center" style="border:1px solid;border-collapse:collapse;" solid>
                <tr>
                    <td style="border:1px solid;border-right:0px;">核准工作時間：&nbsp;{!!$work_date_Y!!}年{!!$work_date_M!!}月{!!$work_date_D!!}日&nbsp;&nbsp;{!!$chk_emp_topic[50]!!}&nbsp;/&nbsp;&nbsp;{!!$chk_emp_topic[51]!!}</td>
                    <td style="border:1px solid;text-align:center ;border-left:0px;"><u>(保存年限3年)</u></td>
                </tr>
            </table>
        </td>
    </tr>

    </tbody>
</table>
<p style="page-break-after:always"></p>
<!-- 2 危害告知 -->
<table border="0" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <p class="MsoNormal" align="center" style="text-align: center; text-indent: 20.0pt; margin-left: -36.0pt; margin-top:0; margin-bottom:0">
                <b>
		<span style="letter-spacing: 1.0pt">
		<font size="6">台灣中油公司煉製事業部</font></span></b></p>

            <p align="center" style="margin-top: 0; margin-bottom: 0"><b>
		<span style="    letter-spacing: 1.0pt">
		<font size="4">大林煉油廠各項作業環境危害因素及安全衛生告知單</font></span></b>
                <span style="float:right"><img src="{!!$qrcode!!}" width="55" height="55" border="0"></span>
        </td>
    </tr>
    <tr>
        <td>
            <table border="0" width="100%" cellpadding="0" style="border-collapse: collapse; border-left-width: 1px; border-right-width: 1px; border-bottom-width: 1px" bordercolordark="#000000" bordercolorlight="#000000">
                <tr>
                    <td width="52%" style="border-style: solid; border-width: 1px" align="left" valign="top">
                        <p class="MsoNormal" style="text-indent: -46.95pt; text-autospace: none; margin-left: 46.95pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[1]!!}【<b>現場一般作業</b>】</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">均應戴安全帽並扣好帽帶;須穿著背面有明顯公司名稱之長袖制服;應穿安全鞋,不得穿涼鞋、拖鞋或打赤腳。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">工場區、儲<span  >(</span>油<span  >)</span>槽區、灌裝<span  >(</span>卸<span  >)</span>區、碼頭區、加油區嚴禁攜入香煙、火柴、打火機等火種及酒類;工作人員於指定吸煙地點吸煙;禁止攜帶檳榔進廠。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">進入煉製現場及油槽防溢堤內大哥大手機等應關機。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">一切工作均須開立工作許可證;使用臨時電源應置漏電斷路器。</span></p>

                        <p class="MsoNormal" style="text-indent: -46.95pt; text-autospace: none; margin-left: 46.95pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[2]!!}【<b>有油氣之危險</b>場所<b>作業</b>】有引火、燃燒、爆炸之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">作業應先簽妥工作許可證。</span><span  style="font-size: 9.5pt;   ">2. </span>
                            <span style="font-size: 9.5pt;   ">
				應使用安全工具、禁止使用行動電話。</span><span  style="font-size: 9.5pt;   ">3.</span><span style="font-size: 9.5pt;   ">非工作人員禁止擅入、現場須準備空氣呼吸器。</span></p>

                        <p class="MsoNormal" style="text-indent: -46.95pt; text-autospace: none; margin-left: 46.95pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[3]!!}【<b>使用電動手工具</b>】漏電工作人員有觸電、感電受電擊之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">馬達外殼要接妥接地線,另移動式電動機具良導體之外殼應有適當接地線。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">電源線之被覆應絕緣良好,不得有裸線,否則應更新。</span></p>

                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[4]!!}【<b>局限空間作業</b>】局限空間可能有油氣、有毒氣體滯留或有缺氧之虞,各承攬人應自備測定儀器做施工環境測定並留紀錄<span  >(</span>局限空間檢修作業安全檢點表<span  >)</span>。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">局限空間作業應有「缺氧作業主管」之指導及監督;塔槽或人孔外應指派一人以上隨時監視,不得擅離現場。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">人員進入侷限空間應隨身攜帶有效之「個人偵測器」,以隨時測定有害物、氧氣等濃度,若有害物質超過在法定容許濃度以上(例如<span  >H2S</span>為<span  >10ppm</span>)或有缺氧(氧氣濃度低於<span  >18%</span>),應配戴有效之個人安全衛生防護具(如空氣面罩等),否則應立即退避到安全處所(往上風方向逃避,緊急時可避入鄰近之控制室)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">空氣中可燃性氣體濃度達爆炸下限之<b><span  >20%</span></b>以上時,應停止所有動火施工,達爆炸下限之<b><span  >30%</span></b>以上時,應通知現場所有人員往上風方向逃避,緊急時可避入臨近之控制室,並報告控制室人員處理,應俟處理妥善,再次申請工作許可證後,才可恢復工作。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">應於安全位置準備有「氧氣救生器」以供人員意外時急救之需。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">內部使用之手提式照明燈,電壓限<span  >24V</span>以下。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				6.</span><span style="font-size: 9.5pt;   ">移動式電動機具良導體之外設應有適當接地線。</span></p>

                        <p class="MsoNormal" style="text-indent: -46.95pt; text-autospace: none; margin-left: 46.95pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[5]!!}【<b>高處作業</b>】包含高空工作車作業;有高處墜落、滑落或摔落、翻覆及感電之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">應全程使用雙掛鉤安全帶,安全帶使用前應先檢點。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">使用施工架前須確實檢點,並於檢點表簽名及簽註檢查時間,未簽名、未簽註檢查時間或掛有檢查不合格(不妥)者均禁止者使用。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">須做好防止從高處墜落之其他安全措施(如搭施工架或張設安全網)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">施工架搭、拆及屋頂、塔槽、管架等應設有供工作人員工作時站、踏及自由上下之設施。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">操作高空工作車前,須依「高空工作車每日作業檢點表」實施檢點,並指定專人指揮、監督及監視後,方可作業;作業時,嚴禁車體任何部份碰觸現場設備、管線或機器。</span></p>

                        <p class="MsoNormal" style="text-indent: -46.95pt; text-autospace: none; margin-left: 46.95pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[6]!!}【<b>電焊作業</b>】漏電時工作人員有觸電、感電受電擊與工作場所可能有可燃性固體、液體或氣體存在,有引火燃燒或爆炸之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">要有檢定合格證書,並經本公司覆查合格(六個月以內)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">電焊機本體要接地、銲接柄應有相當之絕緣耐力及耐熱性。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">裝設自動電擊防止裝置,並設定在自動位置。</span><span  style="font-size: 9.5pt;   ">4.</span><span style="font-size: 9.5pt;   ">現場環境警戒者,應在場看火警戒。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">電源線不得有破皮或裸線、陰極線不得以鐵條、鋼板等充代電源線。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				6.</span><span style="font-size: 9.5pt;   ">電源箱切換開關處或電源配線<b>應裝設漏電斷路器</b>。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				7.</span><span style="font-size: 9.5pt;   ">施工環境應自備監測儀器隨時監測,焊渣、火星應隨即撲滅,可燃物應移離工作現場
				。對工作人員有危害時,應立即暫停施工,並隨即令現場所有人員退避到安全處所。</span></p>
                        <p class="MsoNormal" style="text-indent: -20.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				8.</span><span style="font-size: 9.5pt;   ">地面明、暗溝防火隔離、地面必要時須灑水,且現場須備有<span  >20</span>型滅火器。</span></p>

                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[7]!!}【<b>氣切、焊作業</b>】有乙炔洩漏及乙炔鋼瓶橡膠管回火延燒進鋼瓶爆炸與工作場所可能有可燃性固體、液體或氣體存在,有引火燃燒或爆炸之危險。<span  >(</span>應攜帶合格證照備查<span  >)</span></span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶進、出口應依規定裝設壓力調整器及壓力表,出口壓力指示不可超過正常使用壓力。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶橡膠管應裝設逆止閥,防止乙炔回火延燒。</span><span  style="font-size: 9.5pt;   ">3.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶應豎立並固定及貼危害標誌,禁止倒臥橫置。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶橡膠管禁止與電焊、照明等電源線纏繞糾纏。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶搬運時應將瓶口閥鎖緊。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				6.</span><span style="font-size: 9.5pt;   ">上方有焊、切作業時,乙炔鋼瓶上方應以不燃性材質防護罩遮蔽。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				7.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶應放置在陰涼的處所,應保存在溫度<span  >40</span>℃以下,以防止裂解反應。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				8.</span><span style="font-size: 9.5pt;   ">乙炔鋼瓶與氧氣鋼瓶禁止混合儲存,至少應保持三公尺以上之距離。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				9.</span><span style="font-size: 9.5pt;   ">氧氣鋼瓶瓶口閥禁止加潤滑油潤滑,以防止引火燃燒。</span></p>
                        <p class="MsoNormal" style="text-indent: -13.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				10.</span><span style="font-size: 9.5pt;   ">施工環境應自備監測儀器隨時監測,焊渣、火星應隨即撲滅,可燃物應移離工作現場
				。對工作人員有危害時,應隨即暫停施工,並隨即令現場所有人員退避到安全處所。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				11.</span><span style="font-size: 9.5pt;   ">現場環境警戒者,應在場看火警戒,且現場須備有<span  >20</span>型滅火器。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				12.</span><span style="font-size: 9.5pt;   ">地面明、暗溝防火隔離、地面必要時須灑水。</span></p>

                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[8]!!}【<b>危險性機械操作</b>】</span><span  style="font-size: 9.5pt;   ">1.</span><span style="font-size: 9.5pt;   ">吊升荷重三公噸以上之固定式起重機、移動式起重機、人字臂起重桿。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(1)</span><span style="font-size: 9.5pt;   ">均要有檢查合格證,且在檢查有效時間內才可使用。(應攜帶證件備查或影本)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(2)</span><span style="font-size: 9.5pt;   ">須由危險性機械操作訓練合格者實施檢點,檢查不合格禁止使用。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(3)</span><span style="font-size: 9.5pt;   ">操作人員:應受危險性機械操作人員安全訓練合格(應攜帶受訓合格證照或影本備查)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">從事下列工作,應指派受特殊作業安全衛生教育合格者擔任(應攜帶合格證照備查)。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(1)</span><span style="font-size: 9.5pt;   ">吊升荷重未滿三公噸之固定式起重機操作。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(2)</span><span style="font-size: 9.5pt;   ">吊升荷重未滿三公噸之移動式起重機操作。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(3)</span><span style="font-size: 9.5pt;   ">吊升荷重未滿三公噸之人字臂起重桿操作。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				&nbsp;&nbsp;&nbsp;&nbsp; (4)</span><span style="font-size: 9.5pt;   ">非破壞檢驗之輻射設備之裝置管理及操作。</span></p>

                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[9]!!}【<b>游離輻射作業</b>】:放射線檢照會影響週圍人員健康及本廠射輻射儀器偵測信號<span  >(</span>如第二媒組工場<span  >)</span>。(應攜帶合格證照備查)</span><span  style="font-size: 9.5pt;   ">1.</span><span style="font-size: 9.5pt;   ">游離輻射操作者須有操作執照;工作許可證後須有游離作業附加檢點表。</span><span style="    font-size: 9.5pt"  >2</span><span  style="font-size: 9.5pt;   ">.</span><span style="font-size: 9.5pt;   ">放射線工作四周輻射劑量率大於<span  >0.5</span>μ<span  >Sv/Hr</span>須加圍警戒或警告標示。</span><span  style="font-size: 9.5pt;   ">3.</span><span style="font-size: 9.5pt;   ">會影響輻射控製儀器之工場須事先告知。</span></p></td>
                    <td width="48%" style="border-style: solid; border-width: 1px" align="left" valign="top">

                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 16.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[10]!!}【<b>營造業作業</b>】
				從事下列工作,應指派受營造業作業主管安全衛生教育合格者在場指揮、監督管理(<b>應攜帶合格證照備查</b>)</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">地面開挖深度達<span  >1.5</span>公尺以上或有崩塌之虞者:應指派擋土支撐作業主管。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">釘模板作業:應指派模板支撐作業主管。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">施工架搭、拆工作:應指派施工架組配作業主管。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[11]!!}【<b>有害作業</b>】從事下列工作,應指派受有害作業安全衛生教育合格者擔任指揮、監督管理(<b>應攜帶合格證照備查</b>)</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">油漆作業有機溶劑作業主管。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">油槽、塔槽清洗等局限空間作業:缺氧作業主管。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">噴沙除銹作業;粉塵作業主管。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[12]!!}【<b>保溫、保冷作業</b>】
				有高處墜落、滑落、摔落或或保溫鋁、鐵皮割傷或高溫燙傷或低溫凍傷之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">應使用雙掛鉤安全帶及做好防止高處墜落之安全措施。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">應備妥耐高溫及防燙及防燙傷防護具。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">應備妥耐低溫及防凍傷防護具。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[13]!!}【<b>油漆、除鏽作業</b>】
				有高處墜落、滑落、摔落或有機溶劑中毒或吸入鐵鏽或遇火源火警之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">應使用雙掛鉤安全帶及做好防止高處墜落之安全防護措施。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">油漆應備妥防護有機溶劑中毒之個人安全衛生防護具;嚴禁火源。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">除銹應備妥防塵口罩、輸氣管面罩及防塵眼鏡供作業勞工使用。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[14]!!}【<b>電氣配線、檢修作業</b>】
				有高處墜落、滑落、摔落或觸電及感電之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">禁止帶電(活線)作業;如須帶電(活線)作業,應再召開工程安全會議討論決定應採取之安全措施及佩帶個人安全衛生防護具防止觸電及感電。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">應使用雙掛鉤安全帶或搭施工架等防止高處墜落、滑落、摔落。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">移動式電動機具良導體之外殼應有適當接地線。</span></p>

                        <p class="MsoNormal" style="text-autospace: none; margin-left: 0cm; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[15]!!}【<b>堆高機作業</b>】</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">堆高機之負載荷重不得超過該機械所能承受之最大荷重。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">堆高機裝卸貨物駛至裝卸貨物場地時應減至安全速度。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">堆高機未配置座椅及後扶架者,不得搭乘。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">堆高機於作業場所或倉儲區域搬運或堆置超長,超寬或超高物件時,應有專人指揮。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">堆高機於駕駛者離開其位置時,應採取將貨叉等放置於地面,並將原動機熄火,並完全煞住車後應將鑰匙取下,不得留置堆高機上。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				6.</span><span style="font-size: 9.5pt;   ">堆高機作業時應注意附近之淨空、通道阻物及周圍之安全,必要時設專人指揮。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				7.</span><span style="font-size: 9.5pt;   ">進入工場區進行吊裝作業,以動火許可證加以管制,並禁止用堆高機強制拖拉。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[16]!!}【<b>起重吊掛作業</b>】
				有支撐不穩吊車翻覆、吊物掉落、吊索斷裂及壓、擊傷人之危險。<span  >(</span>應攜帶合格證照備查<span  >)</span></span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">移動式起重機作業前支撐要支撐穩固(地面不得鬆軟或塌陷)。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">吊具(鋼絲索、夾具)要檢查合格;鋼絲索不得有扭結、斷股及腐蝕等情形。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">起重操作(駕駛)人員要有操作移動式起重機吊升荷重五公噸以上受訓合格證照者才可操作。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">起重吊掛應指派受起重吊掛特殊安全衛生訓練合格並有證照者擔任,並負責吊裝作業作業半徑範圍內之統一指揮及指派適當監視人員監視,並警告閒雜人員不得進入作業半徑範圍內。</span></p>

                        <p class="MsoNormal" style="text-autospace: none; margin-left: 0cm; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[17]!!}【<b>轉動機械拆修作業</b>】</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">電源未關斷有誤動作,人員遭轉動葉片或轉軸捲、攪進受傷之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">內容物未排清有受內容物污染噴灑或吸入有毒害物質受傷或中毒之虞。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">未釋壓至常壓有受內容物污染噴灑(傷)之虞。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">未冷卻至常溫有被內容物燙傷之虞。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				5.</span><span style="font-size: 9.5pt;   ">安全防護措施:</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(1)</span><span style="font-size: 9.5pt;   ">電源開關應先檢視確認已上鎖或已做好相關防止誤動作之安全防護施並懸掛檢修標籤,非懸掛此標籤之當事人絕對禁止措擅自更動或取走。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(2)</span><span style="font-size: 9.5pt;   ">連通管線(路)已隔離且內容物已確認排放乾淨。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(3)</span><span style="font-size: 9.5pt;   ">轉動機械(如泵浦)內壓應確認已降至常壓。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(4)</span><span style="font-size: 9.5pt;   ">轉動機械(如泵浦)溫度應確認已冷卻至常溫。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 32.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				(5)</span><span style="font-size: 9.5pt;   ">工作人員應戴妥個人安全衛生防護具(如安全面罩、安全眼罩、防護衣、防護手套…等)。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[18]!!}【<b>道路或地面開挖作業</b>】有崩塌、人員遭活埋或人、車不慎掉落或湧水淘空地基之危險。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">施工範圍應加圍警戒,並裝設夜間警示燈,以防止人、車誤陷。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">道路開挖施工,應事先做好人、車改道,並標示改道示意圖,必要時應派專人擔任道路指揮。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				3.</span><span style="font-size: 9.5pt;   ">地面開挖深度達<span  >1.5</span>公尺以上或有崩塌之虞者,應依規定做好擋土措施;有地下水湧出者,應有抽水設備抽水並做好防止地基被淘空之安全措施。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				4.</span><span style="font-size: 9.5pt;   ">從事露天開挖應指派露天開挖作業主管。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[19]!!}【<b>拆裝盲板作業</b>】</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">須於作業前會同轄區,依本廠「盲板拆裝作業前檢點表」實施檢點。</span></p>
                        <p class="MsoNormal" style="text-indent: -16.0pt; text-autospace: none; margin-left: 28.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">拆裝盲板作業須依檢點表之規定,戴妥適當防護器具,且轄區、監造人員、承攬商職安人員均須在場方可作業。</span></p>

                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[20]!!}【<b>塔槽人孔或換熱器導槽蓋之開放作業</b>】</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">開放作業前須會同轄區依本廠「塔槽人孔或換熱器導槽蓋開放作業前檢點表」實施檢點。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				2.</span><span style="font-size: 9.5pt;   ">高風檢之開放作業,須依檢點表之規定戴妥適當防護器具,且轄區、監造人員、承攬商職安人員均須在場方作業。</span></p>
                        <p class="MsoNormal" style="text-indent: -24.0pt; text-autospace: none; margin-left: 24.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[21]!!}【<b><span  >A</span>級動火作業</b>】動火作業有引起火災爆炸之虞,動火作業前須依「現場<span  >A</span>級動火作業前檢點表」實施檢點並優先使用防火袋及配合其他防護措施後,方可作業。</span></p>
                        <p class="MsoNormal" style="text-autospace: none; margin-left: 0cm; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
                            <span style="font-size: 9.5pt;   ">{!!$chk_danger_topic[22]!!}【<b>其它作業</b>】如有以上各項作業未提到之作業,均視同其它作業。</span></p>
                        <p class="MsoNormal" style="text-indent: -8.0pt; text-autospace: none; margin-left: 20.0pt; margin-right: 6.0pt; margin-top: 0; margin-bottom: 0">
				<span  style="font-size: 9.5pt;   ">
				1.</span><span style="font-size: 9.5pt;   ">各承攬人應隨時做作業環境之檢測,並視其危害因素,做必要之處置。</span></p>
                        <p style="margin-top: 0; margin-bottom: 0">
                            <span  style="font-size: 9.5pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2.</span><span style="font-size: 9.5pt;   ">作業環境對作業勞工有危害之虞時,應立即暫停施工、並隨即令現場所有人員退避到安全處所;立即通知事業主之轄區主管人員處理,俟作業環境處理妥善,再次申請工作許可證後,工作人員方可進入工地恢復工作。</span></td>
                </tr>
                <tr>
                    <td colspan="2" style="height:110pt">
                        <p style="margin-top: 0; margin-bottom: 0" align="left">
                            <font style="font-size: 9.5pt">
                                ※上列各項作業環境危害因素及安全措施應注意事項本人已詳閱並承諾告知所有工作人員。</font><p style="margin-top: 0; margin-bottom: 0" align="left">
                            <font style="font-size: 9.5pt">
                                承攬商工地負責人或承攬商安衛人員:</font><font style="font-size: 13pt">{!! $chk_danger_topic[23] !!}</font><font size="1"  >(簽名)</font><font   style="font-size: 12pt">日 期:&nbsp;&nbsp;{!! $chk_danger_topic[24] !!}</font></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<p style="page-break-after:always"></p>
<!-- 3 定期檢點 -->
@for ($i = 1; $i <= $chk_tip_topic[1]; $i++)
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td height="18">
                <p align="center" style="margin-top: 0; margin-bottom: 0">
                    <font size="6">煉製事業部施工作業環境測量暨工安查核會簽紀錄表</font>
                    <span style="float:right"><img src="{!!$qrcode!!}" width="55" height="55" border="0"></span>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" cellpadding="0" style="border-collapse: collapse; width: 902px" height="973">
                    <tr height="22" style="height: 13pt">
                        <td height="22" colspan="6" style="height: 13pt; font-weight: 700; color: black; font-size: 13.0pt; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <font style="font-size: 14.0pt">一、作業環境紀錄表</font></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td height="22" colspan="6" style="height: 13pt; font-weight: 700; color: black; font-size: 13.0pt; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">承攬商/廠方施工部門作業環境紀錄表(每小時測定一次)</font></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td height="22" style="height: 13pt; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">測定時間</font></td>
                        <td style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">可 燃性氣體(%LEL)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">氧氣(%)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">一氧化碳 (ppm)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">硫化 氫(ppm)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="150">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">測定人員簽名</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="140">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">附註</font></td>
                    </tr>

                    @foreach ($chk_tip_topic[2][$i][1] as $val)
                        <tr style="height: 16pt">
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[1])) {!! $val[1] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                　@if(isset($val[2])) {!! $val[2] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                　@if(isset($val[3])) {!! $val[3]   !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                　@if(isset($val[4])) {!! $val[4]   !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                　@if(isset($val[5])) {!! $val[5]   !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[6])) {!! $val[6]   !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[7])) {!! $val[7]   !!} @endif</td>
                        </tr>
                    @endforeach

                    <tr height="22" style="height: 13pt">
                        <td height="22" colspan="4" style="height: 13pt; font-weight: 700; color: black; font-size: 13.0pt; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">轄區作業環境紀錄表(A 級作業每兩小時測定一次)</font></td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit">　</td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td height="22" style="height: 13pt; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">測定時間</font></td>
                        <td style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">可 燃性氣體(%LEL)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">氧氣(%)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">一氧化碳 (ppm)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">硫化 氫(ppm)</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">測定人員簽名</font></td>
                        <td style="text-align: center; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="font-size: 14.0pt">附註</font></td>
                    </tr>
                    @foreach ($chk_tip_topic[2][$i][2] as $val)
                        <tr style="height: 16pt">
                            <td align="center" style="height: 13pt; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[1])) {!! $val[1] !!} @endif　</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[2])) {!! $val[2] !!} @endif　</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[3])) {!! $val[3] !!} @endif　</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[4])) {!! $val[4] !!} @endif　</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[5])) {!! $val[5] !!} @endif　</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[6])) {!! $val[6] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                @if(isset($val[7])) {!! $val[7] !!} @endif</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td style="color: black; font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                            <font style="font-size: 12pt">一、測量範圍:</font>
                        </td>
                        <td style="color: black; border-left:medium none; border-right:medium none; border-bottom:medium none; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                        </td>
                        <td style="color: black; border-left:medium none; border-right:medium none; border-bottom:medium none; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                        </td>
                        <td style="color: black; border-left:medium none; border-right:medium none; border-bottom:medium none; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                        </td>
                        <td style="color: black; border-left:medium none; border-right:medium none; border-bottom:medium none; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                        </td>
                        <td style="color: black; border-left:medium none; border-right:medium none; border-bottom:medium none; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit">
                        </td>
                        <td style="color: black; font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: medium none; border-right: .5pt solid windowtext; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-color:inherit" height="14">
                        </td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td colspan="7" style="color: black;  font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: .5pt solid black; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">1.動火地點周圍 10 公尺內;明溝、暗溝口及其他易滯存氣體角落。</font></span></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td colspan="7" style="color: black;  font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: .5pt solid black; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">2.局限空崗測量應伸入內部適當位置，且作業時每組作業人員應至少有&nbsp; 1人攜帶偵測器並連續監測。</font></span></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td style="font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: medium none; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <font style="font-size: 12pt">二、准許施工標準:</font></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: medium none; border-right: .5pt solid windowtext; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11">
                        </td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td colspan="2" style="height: 11px; font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: medium none; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">1.可燃性氣體濃度應在爆炸下限(LEL)20%以下。</font></span></td>
                        <td colspan="2" style="font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 15px; padding-right: 1px; padding-top: 1px" height="11">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">2. 氧氣濃度應在 18-21% 。</font></span></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11"></td>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: medium none; border-right: .5pt solid windowtext; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="11">
                        </td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td colspan="2" style="height: 11px; font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: medium none; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">3. 有害氣體應在容許濃度以下。</font></span></td>
                        <td colspan="5" style="font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: medium none; border-right: .5pt solid black; border-top: medium none; border-bottom: medium none; padding-left: 15px; padding-right: 1px; padding-top: 1px" height="11">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <span  ><font style="font-size: 12pt">4.反應器內部氮封等特殊作業場所，不受此限，請另依相關規定施行。</font></span></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td colspan="7" style="height: 12px; font-size: 12.0pt; text-align: left; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif; vertical-align: middle; white-space: nowrap; border-left: .5pt solid windowtext; border-right: .5pt solid black; border-top: medium none; border-bottom: .5pt solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                            <p style="margin-top: -2px; margin-bottom: -2px">
                                <font style="font-size: 12pt">三、測量頻率:局限空間、自音;荐系統、A級動火及通往&nbsp;&nbsp; Flare 管線拆、封盲作業等應採連續監測。</font></td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; height: 20px; font-weight: 700; color: black; font-size: 13.0pt; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit">
                            <p style="margin-top: 0; margin-bottom: 0"><font style="height: 14pt">二、巡邏會簽</font></td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                        <td style="border-left:medium none; border-right:medium none; border-top:medium none; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-bottom-color:inherit" height="20">　</td>
                    </tr>
                    <tr height="22" style="height: 13pt">
                        <td style="height: 21px; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font size="2">查核部門別</font></td>
                        <td style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="21" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font size="2">查核時間</font></td>
                        <td style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="21" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font size="2">簽名&nbsp;</font></td>
                        <td colspan="3" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="21" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font size="2">查核或巡邏情形</font></td>
                        <td style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="21" align="center">
                            <p style="margin-top: 0; margin-bottom: 0"><font size="2">附註</font></td>
                    </tr>
                    @foreach ($chk_tip_topic[2][$i][3] as $val)
                        <tr style="height: 16pt">
                            <td align="center" style="height: 20px; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                                　@if(isset($val[1])) {!! $val[1] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="20">
                                　@if(isset($val[2])) {!! $val[2] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="20">
                                @if(isset($val[3])) {!! $val[3] !!} @endif</td>
                            <td align="center" colspan="3" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid black; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="20">
                                　@if(isset($val[4])) {!! $val[4] !!} @endif</td>
                            <td align="center" style="color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid black; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="20">
                                　@if(isset($val[5])) {!! $val[5] !!} @endif</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="font-size: 12.0pt; color: black; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext;  padding-right: 1px; padding-top: 1px; border-top-color:inherit" colspan="7">
                            <p style="margin-top: 2px; margin-bottom: 2px">
                                <font style="font-size: 12pt">巡查重點:</font></p>
                            <span  >
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">1.職安人員在場，如屬特殊作業，特殊作業主管務必在場督導</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">2.偵測器功能須正常;環境依照規定測量並記錄。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">3.
                            高處作業設有防墜措施，並穿妥安全帶;搭梨須合乎規定(走梯、爬梯、欄桿、扶手、踏板、腳趾板、塑膠套、檢點表)。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">
                            4.動火作業現場看火員須在場;如為高處動火，須設置防止火星噴濺之防火毯等防火措施。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">5.
                            乙烘及氧氣瓶須立妥且加上不燃性護帽，軟管完好且確實以管束束緊，並設置逆止閥。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">6. 電焊機已接地妥並設有自動電擊防止裝置(交流型)&nbsp;&nbsp;&nbsp;
                            ，電線、電銲柄無破損且絕緣良好。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">7. 接用電源時設有漏電斷路器;電器設備接地正常。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">8. 手提滅火器為&nbsp; 20
                            型，壓力正常;香菸、打火機、酒精性飲料及檳榔等違禁品不得攜入廠內; 5S須維持良好。</font></p>
                            <p style="margin-top: 2px; margin-bottom: 2px">
                            <font style="font-size: 12pt">9.
                            局限空間作業(缺氧作業主管、人員進出聲名、人孔管理、生命呼叫器、氧氣救生器、空氣呼吸器、救生索、通風)。</font></p>
                            <p style="margin-top: -2px; margin-bottom: -2px">
                            <font style="font-size: 12pt">10.油槽、塔槽內手提照明為防爆型且電源、為&nbsp; 24v
                            以下。</font></span></td>
                    </tr>
                    </span>
                </table>
            </td>
        </tr>
    </table>
    <p style="page-break-after:always"></p>
@endfor

<!-- 4 圖片 -->
@for ($i = 1; $i <= $chk_photo_count; $i++)
    <table width="100%" height="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" colspan="2" style="height:50pt">
                <font size="6">煉製事業部施工作業ＡＰＰ拍攝照片紀錄 @if(isset($chk_no[$i])){!! $chk_no[$i] !!}@endif</font>
                <span style="float:right"><img src="{!!$qrcode!!}" width="55" height="55" border="0"></span>
            </td>
        </tr>
        @foreach ($chk_photo[$i] as $key => $val)
            @if(($key%2) === 1) <tr height="33%"> @endif
                <td align="center" valign="top" style="width:50%; color: black; font-size: 13.0pt; font-weight: 400; font-style: normal; text-decoration: none;  , serif;   vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                    　@if(isset($val['title'])) <div style="">{!! $val['title'] !!}</div> @endif
                    @if(isset($val['img'])) <div >{!! $val['img'] !!}</div> @endif
                </td>
                @if(($key%2) === 0) </tr> @endif
        @endforeach
    </table>
    <p style="page-break-after:always"></p>
@endfor
</body>
</html>

