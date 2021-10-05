{{-- resources/views/print.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title_prefix', __('sys_base.base_title'))
        @yield('title', __('sys_base.base_title'))</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 4.0.0 -->
{{--    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">--}}
    <style>
        .page-break {
            page-break-after: always;
        }
        body {
            font-family: msyh, DejaVu Sans,sans-serif;
        }
    </style>
</head>
<body>
    <div class="container">
        {!! $content !!}
    </div>
</body>
</html>
