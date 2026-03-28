@extends('theme::layouts.master')

@section('layout-content')
    <div class="row">
        <aside id="sidebar" class="col-md-3" role="complementary" aria-label="Sidebar navigation">
            @include('theme::partials.sidebar')
            @yield('sidebar')
        </aside>
        <div id="main-column" class="col-md-9">
            @yield('title-block')
            @yield('before-content')
            <div id="content">
                @yield('content')
            </div>
            @yield('after-content')
        </div>
    </div>
@endsection
