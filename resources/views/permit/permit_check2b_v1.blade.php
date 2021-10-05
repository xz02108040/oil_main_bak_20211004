@foreach ($ans['data'] as $page => $items)
@if ($page > 1)<p style='page-break-after:always'> </p>@endif

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <style type="text/css">
        @page{

            margin: cm 1cm cm 1cm ;

        }

    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>大林煉油廠局限空間人員進出時間簽名表</title>
</head>

<body>
<table width="700" style="font-family: 標楷體; font-size: 14pt;" border="0" align="center">
    <tbody>
    <tr>
        <td width="700" height="64" style="text-align: center; font-size: 20pt;">大林煉油廠局限空間人員進出時間簽名表</td>
    </tr>

    <tr>
        <td height="30">工場(課)/承攬商：{!!$ans['supply']!!}</td>
    </tr>

    <tr>
        <td height="20" align="center">
            <table width="700" border="1" style="border:1px solid;border-collapse:collapse;text-align:center;" solid>
                <tr>
                    <td width="140" height="35" style="border:1px solid;">設備名稱</td>
                    <td width="140" style="border:1px solid;">姓&nbsp;&nbsp;&nbsp;&nbsp;名</td>
                    <td width="140" style="border:1px solid;">進入時間</td>
                    <td width="140" style="border:1px solid;">出來時間</td>
                    <td width="140" style="border:1px solid;">備&nbsp;&nbsp;&nbsp;&nbsp;註</td>
                </tr>
                @foreach ($items as $item)
                <tr>
                    <td height="35" style="border:1px solid;">{!!$item[1]!!}</td>
                    <td style="border:1px solid;">{!!$item[2]!!}</td>
                    <td style="border:1px solid;">{!!$item[3]!!}</td>
                    <td style="border:1px solid;">{!!$item[4]!!}</td>
                    <td style="border:1px solid;">{!!$item[5]!!}</td>
                </tr>
                @endforeach
            </table></td>
    </tr>
    <tr>
        <td height="20" >&nbsp;</td>
    </tr>
    <tr>
        <td height="20" style="text-align: right;">570-ISM-0C</td>
    </tr>
    <tr>
        <td height="10" style="text-align: center;font-size: 10pt;">第 {!!$page!!} 頁/共 {!!$ans['total_page']!!} 頁</td>
    </tr>

    </tbody>
</table>
</body>
</html>
@endforeach