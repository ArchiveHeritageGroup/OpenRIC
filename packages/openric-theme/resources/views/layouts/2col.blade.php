@extends('theme::layouts.master')

@section('layout-content')
    <div class="row">
        <div id="main-column" class="col-md-12">
            @yield('title-block')
            @yield('before-content')
            <div id="content">
                @yield('content')
            </div>
            @yield('after-content')
        </div>
    </div>
@endsection
