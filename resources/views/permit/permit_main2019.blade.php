<html lang="zh-Hant-TW">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{$head_title}}</title>
    <style>
        body{font-family:cursive,sans-serif;}
    </style>
</head>

<body topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" marginwidth="0" marginheight="0">

<table border="0" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td height="18">
            <p align="justify">
                <img border="0" src="images/report/web03-u4e2du6cb9@2x.png" width="21" height="21">
                <span style="line-height: 115%; color: #8585C3; position: relative; top: 1.5pt">正聯(轄區部門收存)</span>
                <span style="line-height: 115%; color: #3D3F3F; position: relative; top: 1.5pt; text-decoration: underline">煉製事業部工作許可證</span>
                <span style="font-size:10px;">最後修改時間：<u>{{$last_updated_at}}</u></span>
            </p>
            <div style="position: absolute; width: 51px; height: 55px; z-index: 1; left: 792px; top: 5px" id="layer1">
                <img border="0" src="{{$qrcode}}" width="80" height="80">
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <table border="1" cellpadding="0" style="border-collapse: collapse; width: 761px; border-right-width:0px" height="29" bordercolor="#FFFFFF">
                <tr >
                    <td style="height: 29px; color: black; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-left-style:solid; border-left-width:1px; border-right-style:none; border-right-width:medium; border-top-style:solid; border-top-width:1px; border-bottom-style:solid; border-bottom-width:1px" width="758" bordercolor="#FFFFFF">
                        <p style="margin-top: 0; margin-bottom: 0">
                            <span style="font-size:10px;">
                                <b>申請時間：</b><u>{{$apply_date}}</u> 本證編號：<u>{{$permit_no}}</u><br>
                                <b>工作時間：</b><u>{{$work_time}}</u><br>
                                <b>工程作業分級：</b>{{$permit_danger_a}}A級(高危險作業)&nbsp;&nbsp;{{$permit_danger_b}}B級(危險作業)&nbsp;&nbsp;{{$permit_danger_c}}C級(低危險作業)<br>
                                (1)施工部門：<span style="font-size:6px;"><u>{{$dept_name5}} </u></span>
                                轄區部門：<span style="font-size:6px;"><u>{{$dept_name2}} </u></span>
                                監造部門：<span style="font-size:6px;"><u>{{$dept_name1}} </u></span><br/>
                                承攬商：<u> {{$supply}}</u> 工作人員人數：<u> {{$supply_men}} </u> 車輛車號：<u>{{$supply_car}}</u><br/>
                                (2)施工地點：<u>{{$work_place}}</u> 工程案號：<u>{{$project_no}} </u>
                            </span>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td height="331">
            <table border="1" cellpadding="0" style="border-collapse: collapse; width: 902px" bordercolor="#FFFFFF">
                <tr >
                    <td style="border-style:solid; border-width:1px; height: 29px; color: black; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" bordercolor="#FFFFFF">
                        <p style="margin-top: 0; margin-bottom: 0">
                            <span style="font-size:10px;">
                                (3)工作內容：<span style="font-size:8px;"><u>{{$work_memo}}</u></span><br>
                                (4)許可工作項目：{{$chk_isOvertime}}<u>預計延時工作</u><br>
                                非動火：{{$chk_workitem[1]}} 高處作業 {{$chk_workitem[2]}} 油漆作業 {{$chk_workitem[3]}} 油氣管線拆修 {{$chk_workitem[4]}} 電器檢修 {{$chk_workitem[5]}} 海上作業 {{$chk_workitem[6]}} 開挖作業 {{$chk_workitem[7]}} 保溫保冷作業<br>
                                {{$chk_workitem[8]}} 機動車輛、引擎進入廠區內部 {{$chk_workitem[9]}} 其他：<span style="font-size:6px;"><u>{{$chk_workitem[10]}}</u></span> <br/>
                                動火：{{$chk_workitem[20]}} 砂輪機 {{$chk_workitem[21]}} 電、氣焊 {{$chk_workitem[22]}} 發電機發電 {{$chk_workitem[23]}} 噴砂、鋼絲刷除鏽 {{$chk_workitem[24]}} 切割金屬 {{$chk_workitem[25]}} 混泥土破碎機 {{$chk_workitem[26]}} 其他：<span style="font-size:8px;"><u>{{$chk_workitem[27]}}</u></span> <br>
                                局限空間：{{$chk_workitem[30]}} 坑洞、方井、涵洞、油水池等內部作業 {{$chk_workitem[31]}} 煉儲設備內部 {{$chk_workitem[32]}} 其他：<span style="font-size:8px;"><u>{{$chk_workitem[33]}}</u></span><br>
                                (5)附加作業檢點：<u>{{$permit_check}}</u><br>
                                (6)管線或設備之內容物 ：<br>
                                □ 可燃性氣體(天然氣、氫氣、燃料氣等) □易燃液體(汽油、煤油、正己烷等) □ 有害性氣體(硫化氫、氨氣、氮氧等)<br>
                                □ 化學藥劑(抗蝕劑、抗污劑等) □ 酸鹼(硫酸、氫氧化鈉等) □ 熱水、蒸氣 □ 空氣(通風完善) □ 其他<u>____________</u><br>
                                (7)簽發前撿點事項(與許可工作有關項目，認無問題者打【v】，認無關項目者畫【=】) 監造人員：
                                <img src="{{$sign_url1}}" class="sign_img" width="80" height="20">
                            </span>
                        </p>
                    </td>
                </tr>
            </table>
            <table border="1" cellpadding="0" style="border-top:1px #000000 ;border-collapse: collapse; width: 902px" height="29" bordercolor="#FFFFFF">
                <tr>
                    <td style="border-style:solid; border-width:1px; height: 23px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" bordercolor="#FFFFFF">
                        <span style="font-size:10px;"><b>(8)施工安全檢點(發包工程由承攬商負責/廠方施工由施工部門負責，監造部門監督) </b></span>
                    </td>
                </tr>
                <tr>
                    <td style="border-style:solid; border-width:1px; height: 29px; color: black;  font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="893" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                        {{$chk_supply_topic[1]}} 每一施工現場十公尺內應備有 20 型以上手提滅火器<br>
                        {{$chk_supply_topic[2]}} 已備有校正合格四用氣體偵測器，並連續監測及每小時記錄備查<br>
                        {{$chk_supply_topic[3]}} 二公尺以上無標準平台之高架作業已備妥
                            {{$chk_supply_topic[4]}} 標準施工架
                            {{$chk_supply_topic[5]}} 安全網
                            {{$chk_supply_topic[6]}} 安全帶
                            {{$chk_supply_topic[7]}} 其他 {{$chk_supply_topic[8]}}<br>
                        {{$chk_supply_topic[9]}} 危險場所機具使用：{{$chk_supply_topic[10]}} 防爆電氣設備 {{$chk_supply_topic[11]}} 安全工具<br>
                        {{$chk_supply_topic[20]}} 自已備有個人防護具：
                            {{$chk_supply_topic[21]}} 防塵口罩
                            {{$chk_supply_topic[22]}} 防毒口罩
                            {{$chk_supply_topic[23]}} 防毒面具
                            {{$chk_supply_topic[24]}} 自給式空氣呼吸器
                            {{$chk_supply_topic[25]}} 輸氣管式空氣呼吸器
                            {{$chk_supply_topic[26]}} 氧氣救生器 <br>
                            {{$chk_supply_topic[27]}} 防護衣褲
                            {{$chk_supply_topic[28]}} 防酸鹼手套
                            {{$chk_supply_topic[29]}} 絕緣手套
                            {{$chk_supply_topic[30]}} 防護眼罩
                            {{$chk_supply_topic[31]}} 救生索
                            {{$chk_supply_topic[32]}} S0S自動警報器(局限空間作業時)<br>
                        {{$chk_supply_topic[40]}} 看火者：<u>{{$chk_supply_topic[41]}}</u>
                        {{$chk_supply_topic[42]}} 人孔監視者：<u>{{$chk_supply_topic[43]}}</u><br>
                        {{$chk_supply_topic[44]}} 施工人員(人數、姓名或另附名冊)：<u>{{$chk_supply_topic[45]}}</u><br><u>{{$chk_supply_topic[46]}}</u><br>
                        {{$chk_supply_topic[47]}} 缺氧作業主管：<u>{{$chk_supply_topic[48]}}</u>
                        {{$chk_supply_topic[49]}} 施工架組配作業主管：<u>{{$chk_supply_topic[50]}}</u><br>
                        {{$chk_supply_topic[29]}} 起重/ {{$chk_supply_topic[30]}} 吊掛人員：<u>{{$chk_supply_topic[31]}}&nbsp;/ {{$chk_supply_topic[32]}}</u>
                        {{$chk_supply_topic[49]}}有機溶劑作業主管：<u>{{$chk_supply_topic[49]}}</u>
                        </span>
                    </td>
                </tr>
            </table>
            <table border="1" cellpadding="0" style="border-collapse: collapse; width: 902px" height="29" bordercolor="#FFFFFF">
                <tr height="22" style="height: 16.5pt">
                    <td style="border-style:solid; border-width:1px; height: 29px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="79" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">□ 廠方帶班者：(簽名)
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="80" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            <div style="width:80px;height:20px;"><img src="{{$sign_url2}}" class="sign_img" width="80" height="20"></div>
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="733" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">&nbsp;&nbsp;&nbsp;&nbsp;
                            □ 其他作業主管：<u>___________</u>
                        </span>
                    </td>
                </tr>
            </table>
            <table border="1" cellpadding="0" style="border-collapse: collapse; width: 902px" height="29" bordercolor="#FFFFFF">
                <tr height="22" style="height: 16.5pt">
                    <td style="border-style:solid; border-width:1px; height: 29px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="128" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            □ 承攬商：職安衛人員：(簽名)
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="80" height="29" bordercolor="#FFFFFF">
                        <div style="width:80px;height:20px;">
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                        </div>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="55" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            (簽名)
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="70" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            工地負責人：
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="92" height="29" bordercolor="#FFFFFF">
                        <div style="width:80px;height:20px;">
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                        </div>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="39" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            (簽名)
                        </span>
                    </td>
                    <td style="border-style:solid; border-width:1px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="458" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            電話：<u>______________</u>
                        </span>
                    </td>
                </tr>
            </table>
            <table border="1" cellpadding="0" style="border-top:1px #000000 ;border-collapse: collapse; width: 902px" bordercolor="#FFFFFF">
                <tr>
                    <td style="border-style:solid; border-width:1px; height: 23px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" bordercolor="#FFFFFF">
                        <span style="font-size:10px;"><b>(9)環境安全檢點(轄區)</b></span>
                    </td>
                </tr>
                <tr>
                    <td style="border-style:solid; border-width:1px; height: 29px; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="489" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                        □ 設備或管線原存物質<u>____________________________________________________</u><br>
                        □ 設備、管線已釋壓並吹驅乾淨或清洗
                        □ 確認進出口已  □ 關斷 □ 加盲 □ 掛牌 □ 盲板圈標示已掛於現場 <br>
                        □ 已備妥通風設備 □ 電源已隔離、加鎖及掛牌標示<br>
                        □ 施工現場十公尺內或下方之晴溝口、方井、電纜溝 □已堵塞並密封□地面已無遺浮油、雜物及可燃物，確已做好安全處理 <br>
                        □ 施工現場十公尺內已備妥 □ 手提減火器 □ 輪架式滅火車 □ 高壓噴槍 □ 消防水帶接妥清防栓 □ 其它 <br>
                        □ 緊急事故時，承攬商疏散到指定 地點：<u>_________________</u> 聯絡人：<u>_________________</u>&nbsp;電話：<u>_________________</u><br>
                        □ 作業前環境檢測：
                        </span>
                    </td>
                </tr>
            </table>
            <table border="0" cellpadding="0" style="border-collapse: collapse; width: 902px" height="29">
                <tr height="22" style="height:16.5pt">
                    <td width="76" style="height: 14px; width: 57pt; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">檢測項目</span>
                    </td>
                    <td style="width: 123px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="14">
                        <span style="font-size:10px;">可燃性氣體(%LEL)</span>
                    </td>
                    <td style="width: 57px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="14">
                        <span style="font-size:10px;">氧氣(%)</span>
                    </td>
                    <td style="width: 90px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="14">
                        <span style="font-size:10px;">一氧化碳 (ppm)</span>
                    </td>
                    <td style="width: 114px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="14">
                        <span style="font-size:10px;">硫化 氫(ppm)</span>
                    </td>
                    <td style="width: 36px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid windowtext; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" height="14">
                        <span style="font-size:10px;">其他</span>
                    </td>
                    <td rowspan="2" style="width: 62px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">檢測時間</span>
                    </td>
                    <td rowspan="2" style="width: 144px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">簽名</span>
                    </td>
                </tr>
                <tr height="22" style="height:16.5pt">
                    <td style="height: 20px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">安全值</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="123" height="20">
                        <span style="font-size:10px;"><20%</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="57" height="20">
                        <span style="font-size:10px;">18-21%</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="90" height="20">
                        <span style="font-size:10px;"><35ppm</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="114" height="20">
                        <span style="font-size:10px;"><10ppm</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-right-color:windowtext" width="36" height="20">
                    </td>
                </tr>
                <tr height="22" style="height:16.5pt">
                    <td style="height: 24px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">施工人員</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="123" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="57" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="90" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="114" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="36" height="24">
                        　</td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="62" height="24">
                        　</td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="144" height="24">
                        <div style="width:80px;height:20px;">
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                        </div>
                    </td>
                </tr>
                <tr height="22" style="height:16.5pt">
                    <td style="height: 24px; text-align: center; color: black; font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-left: 1px solid windowtext; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px">
                        <span style="font-size:10px;">轄區人員</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="123" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="57" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="90" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="114" height="24">
                        <span style="font-size:10px;">/</span>
                    </td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="36" height="24">
                        　</td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="62" height="24">
                        　</td>
                    <td style="text-align: center; color: black; font-style: normal; text-decoration: none;   vertical-align: middle; white-space: nowrap; border-left: 1px solid; border-right: 1px solid windowtext; border-top: 1px solid; border-bottom: 1px solid windowtext; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="144" height="24">
                        <div style="width:80px;height:20px;">
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                        </div>
                    </td>
                </tr>
            </table>
            <table border="1" cellpadding="0" style="border-collapse: collapse; width: 902px; border-left-width:0px; border-right-width:0px; border-bottom-width:0px" height="29" bordercolor="#FFFFFF">
                <tr height="22" style="height: 16.5pt">
                    <td style="border-left:1px solid #FFFFFF; border-right:1px solid #FFFFFF; height: 36px; color: black;  font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-top-style:solid; border-top-width:1px; border-bottom-style:solid; border-bottom-width:1px" width="893" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            轄區：檢點者：<u><img src="{{$sign_url3}}" class="sign_img" width="80" height="20"></u>
                            複槍者：<u><img src="{{$sign_url3}}" class="sign_img" width="80" height="20"></u>
                            連繫者：□ 專任 □ 機動<u><img src="{{$sign_url3}}" class="sign_img" width="80" height="20"></u>
                            電話<u>＿＿＿＿＿＿＿＿＿＿</u>
                        </span>
                    </td>
                </tr>
            </table>
            <table border="0" cellpadding="0" style="border-top:1px solid;border-bottom:1px solid;border-collapse: collapse; width: 902px" bordercolor="#000000">
                <tr>
                    <td style="border-right:1px solid; width: 400px; height: 120px; color: black;    font-style: normal; text-decoration: none;      vertical-align: top; white-space: nowrap; border-left: medium none; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px" rowspan="2" align="left" bordercolor="#000000">
                        <span style="font-size:10px;">(10) 備註： (工安叮嚀與重要提醒事項)</span>
                    </td>
                    <td style="border-right:1px solid; width: 500px; color: black;font-style: normal; text-decoration: none; vertical-align: middle; white-space: nowrap; border-right: medium none; border-top: 1px solid; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-left-color:inherit" colspan="6" bordercolor="#000000">
                        <span style="font-size:10px;">
                            □ A 級作業日 前已實施現場會勘
                            □ 非 A 級作業<br/>
                            □ A 級作業及關鍵性設備第一次施工前現場會勘<br/>
                            現場合勘狀況說明：
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; border-right: medium none; border-top: medium none; border-bottom: medium none; padding-left: 1px; padding-right: 1px; padding-top: 1px; border-left-color:inherit" width="43" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">□ 監造：</span>
                    </td>
                    <td style="border:medium none; color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="96" bordercolor="#FFFFFF">
                        <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                    </td>
                    <td style="border:medium none; color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="50" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">□ 施工：</span>
                    </td>
                    <td style="border:medium none; color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="92" bordercolor="#FFFFFF">
                        <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                    </td>
                    <td style="border:medium none; color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="49" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">□ 轄區：</span>
                    </td>
                    <td style="border:medium none; color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="116" bordercolor="#FFFFFF">
                        <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td bordercolor="#FFFFFF" style="padding-top:10px;padding-bottom:10px;">
            <span style="font-size:10px;">
                延時工作時間：
                <u>______</u>&nbsp;&nbsp;時 </font><u>______</u>分 至<u>______</u>&nbsp;時 </font><u>______</u>分&nbsp;&nbsp;&nbsp;&nbsp;
                □ 監造：<img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                □ 施工：<img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                □ 轄區：<img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
            </span>
        </td>
    </tr>
    <tr>
        <td bordercolor="#FFFFFF">
            <table cellpadding="0" style="border-top:2px dotted #000000 ;border-collapse: collapse; width: 902px" height="29" bordercolor="#FFFFFF">
                <tr>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="400" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            會簽部門：
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                            （簽名）
                        </span>

                    </td>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="502" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            會簽主簽人簽章：
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                            （簽名）
                        </span>

                    </td>
                </tr>
                <tr>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="400" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            轄區主簽者簽章：
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                            （簽名）
                        </span>

                    </td>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="502" height="29" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                            轄區經理簽章：
                            <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                            （簽名）
                        </span>

                    </td>
                </tr>
                <tr>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="893" bordercolor="#FFFFFF">
                        <span style="font-size:10px;"><u>
                            核准工作時間：&nbsp; 年&nbsp; 月 日&nbsp; 土午&nbsp;至上午&nbsp; &nbsp;
                        </u></span>
                    </td>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="893" bordercolor="#FFFFFF">
                        <span style="font-size:10px;"><u>
                            &nbsp; /&nbsp;&nbsp; 下午&nbsp; 至&nbsp;&nbsp; 下午
                        </u></span>
                    </td>
                </tr>
            </table>
            <table border="0" cellpadding="0" style="border-collapse: collapse; width: 902px" bordercolor="#FFFFFF">
                <tr>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="300" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                                <u>轄區回收簽章欄：&nbsp;□環境已整理及作好防護措施</u>
                        </span>
                    </td>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="400" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                                <u>承攬商職安人員(或廠工) 簽認：</u>
                                <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                                （簽名）
                        </span>
                    </td>
                    <td style="color: black;    font-style: normal; text-decoration: none;      vertical-align: middle; white-space: nowrap; padding-left: 1px; padding-right: 1px; padding-top: 1px" width="200" bordercolor="#FFFFFF">
                        <span style="font-size:10px;">
                                <u>轄區簽認：</u>
                                <img src="{{$sign_url3}}" class="sign_img" width="80" height="20">
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center" colspan="3">
                        <span style="font-size:10px;"><u>&nbsp;(保存年限：3年)</u></span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>

</html>

