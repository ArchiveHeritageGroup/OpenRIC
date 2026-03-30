@extends('theme::layouts.1col')
@section('title', 'Add Gallery Artwork')
@section('body-class', 'gallery add')
@section('content')
  @include('openric-gallery::gallery.edit', ['artwork' => null, 'isNew' => true])
@endsection
