@extends('theme::layouts.master')

@section('layout-content')
    <div class="row">
        <aside id="left-column" class="col-md-3" role="complementary" aria-label="Sidebar navigation">
            @include('theme::partials.sidebar')
            @yield('sidebar')
        </aside>
        <div id="main-column" class="col-md-6">
            @yield('title-block')
            @yield('before-content')
            <div id="content">
                @yield('content')
            </div>
            @yield('after-content')
        </div>
        <aside id="right-column" class="col-md-3" role="complementary" aria-label="Additional information">
            @yield('right')
        </aside>
    </div>
@endsection
