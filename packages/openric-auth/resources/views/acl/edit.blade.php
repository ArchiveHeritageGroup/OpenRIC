@extends('theme::layouts.1col')
@section('title', 'ISAD Security Fields')
@section('content')
<div class="container py-4">
<h1>ISAD(G) - Security Classification</h1>
<div class="alert alert-info">The security fieldset is included in the information object edit form via the <code>@@include("openric-auth::partials.security-fieldset")</code> partial.</div>
@include('openric-auth::partials.security-fieldset', ['classifications' => $classifications ?? [], 'currentClassificationId' => $currentClassificationId ?? null, 'classificationReason' => $classificationReason ?? ''])
</div>
@endsection
