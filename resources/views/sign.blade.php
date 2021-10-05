{{-- resources/views/admin/dashboard.blade.php --}}

@extends('adminlte.page_sign')

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
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
