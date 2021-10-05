{{-- resources/views/admin/dashboard.blade.php --}}

@extends('template.doorreport_test')

@section('title', __('sys_base.base_title'))



@section('js')
    @if(isset($js))
        <script>{!! $js !!}</script>
    @endif
@stop
