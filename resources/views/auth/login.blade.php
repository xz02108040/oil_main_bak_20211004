@extends('template.login')


@section('css')
    <style type="text/css">
        #container-fluid {
            background-color: #a6e1ec;
            height: auto;
            width:100%;
        }
        html,body{height:100%;background-color: {{ config('mycolor.login_color','#2B6C82') }}};overflow-x: hidden; }


    </style>
@stop
@section('content')

    <div class="container">
        @if(Session::has('message'))
            <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
        @endif
        @if ($errors->any())
            <div class="row">
                @foreach ($errors->all() as $error)
                    <div class='alert-danger alert'>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {!! $error !!}</div>
                @endforeach
            </div>
        @endif
        <div class="row">
            {!! Form::open(['url' => '/login','class'=>'form-signin']) !!}

            <div style="width:100%;text-align : center;padding-bottom : 10px;">
                <h2 class="form-signin-heading"><div style="color:#ffffff; ">{!!Lang::get('sys_comp.COMP').Lang::get('sys_base.base_title')!!}</div></h2>
                {!!Html::image("./images/logo_main.png",'',['width'=>300])!!}
            </div>
            <div class="row" style="display: none">
                <div class="col-md-12"><label for="inputEmail" class=""><font color="#ffffff" size="4px">{!!Lang::get('sys_base.base_40106')!!}</font></label></div>
                <div class="col-md-12">{!! $storeSelect !!}</div>
            </div>
            <div class="row">
                <div class="col-md-12"><label for="inputEmail" class=""><font color="#ffffff" size="4px">{!!Lang::get('sys_base.base_10000')!!}</font></label></div>
                <div class="col-md-12"><input type="text" name="account" id="inputAccount" class="form-control" placeholder="{!!Lang::get('sys_base.base_10002')!!}" required autofocus>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12"><label for="inputPassword" class=""><font color="#ffffff" size="4px">{!!Lang::get('sys_base.base_10001')!!}</font></label></div>
                <div class="col-md-12"><input type="password" name="password" id="inputPassword" class="form-control" placeholder="{!!Lang::get('sys_base.base_10003')!!}" required>
                    <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                </div>
            </div>
            <div class="checkbox" style="padding-left:10px;">
                <label>
                    <input type="checkbox" value="remember-me"> <font color="#ffffff" size="3px">{!!Lang::get('sys_base.base_10014')!!}</font>
                    <div style="width: 40px;display:inline-block;">&nbsp; </div>
                </label>
            </div>
            <button class="btn btn-lg btn-default btn-block" type="submit"><b>{!!Lang::get('sys_base.base_10005')!!}</b></button>
            {!! Form::close() !!}
            <div style="width:100%;text-align : center;">
                <a href="javascript:location.href='https://ra.publicca.hinet.net/SSLQueryCert/SSLQueryCert.jsp?Domain_name='+document.location.hostname"><img
                            src="./images/SSLSeal.gif" width="90" height="126" /></a>
            </div>
            <div style="width:65%;text-align : right;padding-bottom : 10px;">
                <h6 class="form-signin-heading"><div style="color:#ffffff; ">{!!Lang::get('sys_comp.COMP_VERSION',['version'=>$System_Version])!!}</div></h6>
            </div>
        </div>
    </div>




@stop
