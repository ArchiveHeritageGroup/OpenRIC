<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print View - OpenRiC</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; margin: 20px; }
        h1 { font-size: 16pt; margin-bottom: 5px; }
        .meta { color: #666; font-size: 9pt; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 8px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; font-size: 10pt; }
        td { font-size: 10pt; }
        .scope { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:15px">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('display.browse') }}">Back to Browse</a>
    </div>

    <h1>Records
        @if(!empty($parent)) &mdash; {{ $parent->title ?? 'Untitled' }}@endif
        @if(!empty($typeFilter)) ({{ ucfirst($typeFilter) }})@endif
    </h1>
    <div class="meta">{{ $total }} record{{ $total !== 1 ? 's' : '' }} | Printed {{ date('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Identifier</th>
                <th>Title</th>
                <th>Level</th>
                <th>Type</th>
                <th>Scope and Content</th>
            </tr>
        </thead>
        <tbody>
            @forelse($objects as $i => $obj)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $obj->identifier ?? '' }}</td>
                <td>{{ $obj->title ?? 'Untitled' }}</td>
                <td>{{ $obj->level_name ?? '' }}</td>
                <td>{{ ucfirst($obj->object_type ?? '') }}</td>
                <td class="scope">{{ \Illuminate\Support\Str::limit(strip_tags($obj->scope_and_content ?? ''), 200) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#999;">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
