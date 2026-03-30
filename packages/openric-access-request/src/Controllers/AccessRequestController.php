<?php

declare(strict_types=1);

namespace OpenRic\AccessRequest\Controllers;

use OpenRic\AccessRequest\Services\AccessRequestService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AccessRequestController extends Controller
{
    public function __construct(
        protected AccessRequestService $service
    ) {}

    /**
     * Browse all access requests.
     */
    public function browse()
    {
        $requests = $this->service->getAllRequests();

        return view('openric-access-request::browse', compact('requests'));
    }

    /**
     * New access request form.
     */
    public function create()
    {
        return view('openric-access-request::new');
    }

    /**
     * Request access to a specific object.
     */
    public function requestObject(Request $request, string $slug)
    {
        return view('openric-access-request::request-object', compact('slug'));
    }

    /**
     * My requests listing.
     */
    public function myRequests()
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        $requests = $this->service->getMyRequests($userIri);

        return view('openric-access-request::my-requests', compact('requests'));
    }

    /**
     * Pending requests (admin/approver view).
     */
    public function pending()
    {
        $requests = $this->service->getPendingRequests();

        return view('openric-access-request::pending', compact('requests'));
    }

    /**
     * View a single request.
     */
    public function view(string $id)
    {
        $accessRequest = $this->service->getRequest($id);
        abort_unless($accessRequest, 404);

        return view('openric-access-request::view', compact('accessRequest'));
    }

    /**
     * Manage approvers.
     */
    public function approvers()
    {
        $approvers = $this->service->getApprovers();

        return view('openric-access-request::approvers', compact('approvers'));
    }

    /**
     * Store a new access request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'request_type' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'justification' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        
        $this->service->createRequest($userIri, $validated);

        return redirect()->route('accessRequest.myRequests')->with('notice', 'Access request submitted.');
    }

    /**
     * Approve an access request.
     */
    public function approve(Request $request, string $id)
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        
        $this->service->approveRequest($id, $userIri, $request->get('notes'));

        return redirect()->route('accessRequest.pending')->with('notice', 'Request approved.');
    }

    /**
     * Deny an access request.
     */
    public function deny(Request $request, string $id)
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        
        $this->service->denyRequest($id, $userIri, $request->get('reason'));

        return redirect()->route('accessRequest.pending')->with('notice', 'Request denied.');
    }

    /**
     * Add an approver.
     */
    public function addApprover(Request $request)
    {
        $validated = $request->validate([
            'user_iri' => 'required|string',
        ]);

        $this->service->addApprover($validated['user_iri']);

        return redirect()->route('accessRequest.approvers')->with('notice', 'Approver added.');
    }

    /**
     * Remove an approver.
     */
    public function removeApprover(string $id)
    {
        $this->service->removeApprover($id);

        return redirect()->route('accessRequest.approvers')->with('notice', 'Approver removed.');
    }

    /**
     * Cancel an access request (by the requesting user).
     */
    public function cancel(string $id)
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        
        $this->service->cancelRequest($id, $userIri);

        return redirect()->route('accessRequest.myRequests')->with('notice', 'Access request cancelled.');
    }

    /**
     * Store a new object-specific access request.
     */
    public function storeObjectRequest(Request $request)
    {
        $validated = $request->validate([
            'object_id' => 'required|string',
            'description' => 'required|string|max:2000',
            'justification' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        
        $this->service->createRequest($userIri, $validated);

        return redirect()->route('accessRequest.myRequests')->with('notice', 'Object access request submitted.');
    }

    /**
     * Get user IRI from authenticated user.
     */
    private function getUserIri($user): string
    {
        if ($user && method_exists($user, 'getIri')) {
            return $user->getIri();
        }
        
        // Fallback: construct IRI from config
        $baseUri = config('openric.user_base_uri', 'https://ric.theahg.co.za/user');
        return $baseUri . '/' . ($user?->id ?? 'anonymous');
    }
}
