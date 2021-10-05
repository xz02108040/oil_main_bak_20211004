{{-- resources/views/admin/dashboard.blade.php --}}

@extends('template.doorreport')

@section('title', __('sys_base.base_title'))


@section('content')
    {!! $content !!}
@stop

@section('js')
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
