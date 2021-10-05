{{-- resources/views/admin/dashboard.blade.php --}}

@extends('adminlte.page')

@section('title', __('sys_comp.COMP_TITLE').__('sys_base.base_title'))

@section('content_header')
    <h1>{!! $title !!}</h1>
@stop

@section('content')
    <p>{!! $content !!}</p>
@stop

@section('css')
    @if(isset($css))
    <style>{!! $css !!}</style>
    @endif
@stop

@section('js')
    <script>
        $(function () {
            $(".select2").select2();
            $('.form-control').keypress(function (e) {
                if (e.which == 13) {
                    return false;
                }
            });
        });
    </script>
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
