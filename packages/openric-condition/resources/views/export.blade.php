@extends('theme::layouts.1col')
@section('title', 'Export Condition Report')
@section('content')
<h1 class="h3 mb-4">Export Condition Report</h1>
<div class="alert alert-info">Report data has been generated as JSON. Use the browser's download capabilities or copy the data below.</div>
<pre class="bg-light p-3 rounded" style="max-height:500px;overflow-y:auto;">{{ json_encode($report ?? [], JSON_PRETTY_PRINT) }}</pre>
<a href="{{ route('condition.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
@endsection
