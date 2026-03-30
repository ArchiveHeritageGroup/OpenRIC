{{--
  Print Preview — display/print.blade.php
  Adapted from Heratio ahg-display display/print.blade.php
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse - Print Preview</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; }
    h1 { font-size: 18px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; color: #0d6efd; }
    .meta { color: #666; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
    th { background-color: #0d6efd; color: white; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .scope { font-size: 11px; color: #666; max-width: 300px; }
    @media print {
      body { margin: 0; }
      h1 { page-break-after: avoid; }
      tr { page-break-inside: avoid; }
      .no-print { display: none; }
    }
    .print-btn { background: #0d6efd; color: white; border: none; padding: 10px 20px; cursor: pointer; margin-right: 10px; margin-bottom: 20px; }
    .print-btn:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="no-print">
    <button class="print-btn" onclick="window.print()">Print this page</button>
    <button class="print-btn" onclick="window.close()">Close</button>
  </div>

  <h1>
    @if(isset($parent) && $parent)
      {{ e($parent->title ?? '') }} - Contents
    @else
      Browse Results
    @endif
  </h1>

  <div class="meta">
    <strong>Total:</strong> {{ $total ?? 0 }} records |
    <strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}
    @if(!empty($typeFilter))
      | <strong>Type:</strong> {{ ucfirst($typeFilter) }}
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:120px">Identifier</th>
        <th>Title</th>
        <th style="width:100px">Level</th>
        <th style="width:80px">Type</th>
        <th style="width:250px">Scope and Content</th>
      </tr>
    </thead>
    <tbody>
      @if(!empty($objects) && count($objects))
        @foreach($objects as $obj)
          <tr>
            <td>{{ e($obj->identifier ?? '-') }}</td>
            <td><strong>{{ e($obj->title ?? '[Untitled]') }}</strong></td>
            <td>{{ e($obj->level_name ?? '-') }}</td>
            <td>{{ ucfirst($obj->object_type ?? '-') }}</td>
            <td class="scope">{{ e(mb_substr($obj->scope_and_content ?? '', 0, 200)) }}@if(strlen($obj->scope_and_content ?? '') > 200)...@endif</td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="5" style="text-align:center;color:#999;padding:20px;">No records to display.</td>
        </tr>
      @endif
    </tbody>
  </table>

  <div class="meta" style="margin-top: 20px;">
    <em>Printed from OpenRiC Display System</em>
  </div>
</body>
</html>
