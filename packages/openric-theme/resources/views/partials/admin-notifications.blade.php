{{-- Admin notifications — adapted from Heratio admin-notifications.blade.php (46 lines) --}}
{{-- Shows alert bars for pending access requests and background job errors --}}

@if($themeData['isAdmin'] ?? false)
    @php
        $pendingRequests = $themeData['pendingAccessRequestCount'] ?? 0;
        $failedJobs = 0;
        try {
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // Table may not exist
        }
    @endphp

    @if($pendingRequests > 0)
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-0 rounded-0 border-start-0 border-end-0" role="alert">
            <i class="bi bi-shield-exclamation me-2 fs-5"></i>
            <div class="flex-grow-1">
                <strong>{{ $pendingRequests }}</strong> pending security access {{ Str::plural('request', $pendingRequests) }} awaiting review.
            </div>
            @if(Route::has('admin.security.access-requests'))
                <a href="{{ route('admin.security.access-requests') }}" class="btn btn-warning btn-sm ms-2">
                    Review
                </a>
            @endif
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($failedJobs > 0)
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-0 rounded-0 border-start-0 border-end-0" role="alert">
            <i class="bi bi-exclamation-triangle me-2 fs-5"></i>
            <div class="flex-grow-1">
                <strong>{{ $failedJobs }}</strong> background {{ Str::plural('job', $failedJobs) }} failed.
            </div>
            @if(Route::has('admin.failed-jobs'))
                <a href="{{ route('admin.failed-jobs') }}" class="btn btn-danger btn-sm ms-2">
                    Review
                </a>
            @endif
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endif
