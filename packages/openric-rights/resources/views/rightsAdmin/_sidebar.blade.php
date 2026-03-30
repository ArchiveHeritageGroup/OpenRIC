{{-- Rights Admin sidebar navigation --}}
<div class="card">
  <div class="card-header bg-primary text-white"><h5 class="mb-0">Administration</h5></div>
  <div class="list-group list-group-flush">
    <a href="{{ route('rights.admin.index') }}" class="list-group-item list-group-item-action">Dashboard</a>
    <a href="{{ route('rights.admin.embargoes') }}" class="list-group-item list-group-item-action">Embargoes</a>
    <a href="{{ route('rights.admin.orphan-works') }}" class="list-group-item list-group-item-action">Orphan Works</a>
    <a href="{{ route('rights.admin.report') }}" class="list-group-item list-group-item-action">Coverage Report</a>
    <a href="{{ route('rights.admin.statements') }}" class="list-group-item list-group-item-action">Rights Statements</a>
    <a href="{{ route('rights.admin.tk-labels') }}" class="list-group-item list-group-item-action">TK Labels</a>
  </div>
</div>
