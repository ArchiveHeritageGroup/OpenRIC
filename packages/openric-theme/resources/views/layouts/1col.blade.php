@extends('theme::layouts.master')

@section('layout-content')
    <div id="main-column" class="row justify-content-center">
        <div class="col-12">
            @yield('title-block')
            @yield('before-content')
            <div id="content">
                @yield('content')
            </div>
            @yield('after-content')
        </div>
    </div>
@endsection
