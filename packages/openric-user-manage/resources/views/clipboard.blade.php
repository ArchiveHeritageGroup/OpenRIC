@extends('theme::layouts.1col')

@section('title', 'Clipboard')
@section('body-class', 'view clipboard')

@php
$items = $items ?? collect();
@endphp

@section('content')
<div class="container mt-4">
    <h1>Your Clipboard</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <p class="text-muted mb-3">Your clipboard contains saved items for quick access.</p>
    
    @auth
    <div class="mb-3">
        @if($items->count() > 0)
            <form method="POST" action="{{ route('user.clipboard.clear') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Clear all items from clipboard?')">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </form>
        @endif
        <a href="{{ route('home') }}" class="btn btn-secondary">Return to Home</a>
    </div>
    @endauth
    
    @if($items->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Your clipboard is empty. Browse archival descriptions and add items to your clipboard for quick access.
        </div>
        <p class="text-muted">Look for the clipboard icon <i class="fas fa-clipboard"></i> on archival descriptions to add them to your clipboard.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ is_array($item) ? ($item['type'] ?? 'Item') : ($item->type ?? 'Item') }}
                                </span>
                            </td>
                            <td>
                                @if(is_array($item))
                                    <a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] ?? $item['identifier'] ?? 'Untitled' }}</a>
                                @else
                                    <a href="{{ $item->url ?? '#' }}">{{ $item->title ?? $item->identifier ?? 'Untitled' }}</a>
                                @endif
                            </td>
                            <td>
                                @if(is_array($item) && isset($item['added_at']))
                                    {{ \Carbon\Carbon::parse($item['added_at'])->format('Y-m-d H:i') }}
                                @elseif(isset($item->added_at))
                                    {{ \Carbon\Carbon::parse($item->added_at)->format('Y-m-d H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('user.clipboard.remove', is_array($item) ? $item['id'] : $item->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove from clipboard">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <p class="text-muted">Total items: {{ $items->count() }}</p>
        </div>
    @endif
</div>
@endsection
