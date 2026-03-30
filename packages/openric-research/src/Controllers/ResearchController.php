<?php

declare(strict_types=1);

namespace OpenRiC\Research\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\Research\Services\CollaborationService;
use OpenRiC\Research\Services\EntityResolutionService;
use OpenRiC\Research\Services\OdrlService;
use OpenRiC\Research\Services\ResearchService;
use OpenRiC\Research\Services\ValidationQueueService;

/**
 * ResearchController -- Full research portal controller.
 *
 * Adapted from Heratio AhgResearch\Controllers\ResearchController.
 * Every action from the Heratio source is preserved here.
 */
class ResearchController extends Controller
{
    protected ResearchService $service;

    public function __construct()
    {
        $this->service = new ResearchService();
    }

    protected function getResearcherOrRedirect(): object|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        return $researcher;
    }

    protected function getSidebarData(string $active): array
    {
        $unreadNotifications = 0;
        if (Auth::check()) {
            $researcher = $this->service->getResearcherByUserId((int) Auth::id());
            if ($researcher) {
                $unreadNotifications = $this->service->getUnreadNotificationCount((int) $researcher->id);
            }
        }

        return [
            'sidebarActive'       => $active,
            'unreadNotifications' => $unreadNotifications,
        ];
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function index(): RedirectResponse
    {
        return redirect()->route('research.dashboard');
    }

    public function dashboard(): View|RedirectResponse
    {
        $stats = $this->service->getDashboardStats();
        $researcher = null;
        $enhancedData = [];
        $unreadNotifications = 0;
        $recentActivity = [];

        if (Auth::check()) {
            $researcher = $this->service->getResearcherByUserId((int) Auth::id());
            if ($researcher && $researcher->status === 'approved') {
                $enhancedData = $this->service->getEnhancedDashboardData((int) $researcher->id);
                $unreadNotifications = $enhancedData['unread_notifications'] ?? 0;
                $recentActivity = $enhancedData['recent_activity'] ?? [];
            }
        }

        $pendingResearchers = $this->service->getResearchers(['status' => 'pending']);
        $todayBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.booking_date', date('Y-m-d'))
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->select('b.*', 'r.first_name', 'r.last_name', 'rm.name as room_name')
            ->orderBy('b.start_time')->get()->toArray();

        $pendingApprovals = $pendingResearchers;
        $todaySchedule = $todayBookings;
        $recentJournalEntries = $enhancedData['recent_journal'] ?? [];
        $isAdmin = Auth::check();

        return view('research::research.dashboard', array_merge(
            $this->getSidebarData('workspace'),
            compact('stats', 'researcher', 'enhancedData', 'unreadNotifications', 'recentActivity',
                'pendingResearchers', 'pendingApprovals', 'todayBookings', 'todaySchedule',
                'recentJournalEntries', 'isAdmin')
        ));
    }

    // =========================================================================
    // RESEARCHER REGISTRATION
    // =========================================================================

    public function register(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to register');
        }

        $userId = (int) Auth::id();
        $existing = $this->service->getResearcherByUserId($userId);
        $existingResearcher = null;

        if ($existing) {
            if ($existing->status === 'rejected') {
                $existingResearcher = $existing;
            } else {
                return redirect()->route('research.profile');
            }
        }

        $user = DB::table('users')->where('id', $userId)->first();

        if ($request->isMethod('post')) {
            try {
                $data = [
                    'user_id'            => $userId,
                    'title'              => $request->input('title'),
                    'first_name'         => $request->input('first_name'),
                    'last_name'          => $request->input('last_name'),
                    'email'              => $request->input('email'),
                    'phone'              => $request->input('phone'),
                    'affiliation_type'   => $request->input('affiliation_type'),
                    'institution'        => $request->input('institution'),
                    'department'         => $request->input('department'),
                    'position'           => $request->input('position'),
                    'research_interests' => $request->input('research_interests'),
                    'current_project'    => $request->input('current_project'),
                    'orcid_id'           => $request->input('orcid_id'),
                    'id_type'            => $request->input('id_type'),
                    'id_number'          => $request->input('id_number'),
                    'student_id'         => $request->input('student_id'),
                ];

                if ($existingResearcher) {
                    $data['status'] = 'pending';
                    $data['rejection_reason'] = null;
                    DB::table('research_researcher')
                        ->where('id', $existingResearcher->id)
                        ->update($data);

                    return redirect()->route('research.registrationComplete')
                        ->with('success', 'Re-registration submitted for review');
                }

                $this->service->registerResearcher($data);

                return redirect()->route('research.registrationComplete')
                    ->with('success', 'Registration submitted');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return view('research::research.register', array_merge(
            $this->getSidebarData('profile'),
            compact('user', 'existingResearcher')
        ));
    }

    public function registrationComplete(): View
    {
        return view('research::research.registration-complete', $this->getSidebarData('profile'));
    }

    public function publicRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $email = trim((string) $request->input('email'));
            $username = trim((string) $request->input('username'));
            $password = (string) $request->input('password');
            $confirmPassword = (string) $request->input('confirm_password');

            $errors = [];
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address is required';
            }
            if (empty($username) || strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            }
            if (empty($password) || strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }

            $existingUser = DB::table('users')->where('email', $email)->first();
            $existingByUsername = DB::table('users')->where('name', $username)->first();

            if ($existingUser) {
                $errors[] = 'Email address is already registered';
            }
            if ($existingByUsername && (!$existingUser || $existingByUsername->id !== $existingUser->id)) {
                $errors[] = 'Username is already taken';
            }

            if (!empty($errors)) {
                return back()->with('error', implode('<br>', $errors));
            }

            try {
                DB::beginTransaction();

                $userId = DB::table('users')->insertGetId([
                    'name'       => $username,
                    'email'      => $email,
                    'password'   => bcrypt($password),
                    'active'     => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->service->registerResearcher([
                    'user_id'            => $userId,
                    'title'              => $request->input('title'),
                    'first_name'         => $request->input('first_name'),
                    'last_name'          => $request->input('last_name'),
                    'email'              => $email,
                    'phone'              => $request->input('phone'),
                    'affiliation_type'   => $request->input('affiliation_type', 'independent'),
                    'institution'        => $request->input('institution'),
                    'department'         => $request->input('department'),
                    'position'           => $request->input('position'),
                    'research_interests' => $request->input('research_interests'),
                    'current_project'    => $request->input('current_project'),
                    'orcid_id'           => $request->input('orcid_id'),
                    'id_type'            => $request->input('id_type'),
                    'id_number'          => $request->input('id_number'),
                    'student_id'         => $request->input('student_id'),
                ]);

                DB::commit();

                return redirect()->route('research.registrationComplete')
                    ->with('success', 'Registration successful! Pending approval.');
            } catch (\Exception $e) {
                DB::rollBack();

                return back()->with('error', 'Registration failed: ' . $e->getMessage());
            }
        }

        return view('research::research.public-register', $this->getSidebarData(''));
    }

    // =========================================================================
    // PROFILE
    // =========================================================================

    public function profile(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $this->service->updateResearcher((int) $researcher->id, [
                'title'              => $request->input('title'),
                'first_name'         => $request->input('first_name'),
                'last_name'          => $request->input('last_name'),
                'phone'              => $request->input('phone'),
                'affiliation_type'   => $request->input('affiliation_type'),
                'institution'        => $request->input('institution'),
                'department'         => $request->input('department'),
                'position'           => $request->input('position'),
                'research_interests' => $request->input('research_interests'),
                'current_project'    => $request->input('current_project'),
                'orcid_id'           => $request->input('orcid_id'),
            ]);

            return redirect()->route('research.profile')->with('success', 'Profile updated');
        }

        $bookings = $this->service->getResearcherBookings((int) $researcher->id);
        $collections = $this->service->getCollections((int) $researcher->id);
        $savedSearches = $this->service->getSavedSearches((int) $researcher->id);

        return view('research::research.profile', array_merge(
            $this->getSidebarData('profile'),
            compact('researcher', 'bookings', 'collections', 'savedSearches')
        ));
    }

    // =========================================================================
    // ADMIN: MANAGE RESEARCHERS
    // =========================================================================

    public function researchers(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $filter = $request->input('filter', 'all');
        $query = $request->input('q');

        $statusFilter = ($filter !== 'all') ? $filter : null;
        $researchers = $this->service->getResearchers([
            'status' => $statusFilter,
            'search' => $query,
        ]);

        $counts = $this->service->getResearcherCounts();

        return view('research::research.researchers', array_merge(
            $this->getSidebarData('researchers'),
            compact('researchers', 'filter', 'counts', 'query')
        ));
    }

    public function viewResearcher(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcher($id);
        if (!$researcher) {
            abort(404, 'Not found');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('booking_action');
            if ($action === 'approve') {
                $this->service->approveResearcher($id, (int) Auth::id());
                DB::table('users')->where('id', $researcher->user_id)->update(['active' => true]);

                return redirect()->route('research.viewResearcher', $id)->with('success', 'Approved');
            }
            if ($action === 'suspend') {
                $this->service->suspendResearcher($id);

                return redirect()->route('research.viewResearcher', $id)->with('success', 'Suspended');
            }
        }

        $bookings = $this->service->getResearcherBookings($id);

        return view('research::research.view-researcher', array_merge(
            $this->getSidebarData('researchers'),
            compact('researcher', 'bookings')
        ));
    }

    public function approveResearcher(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcher($id);
        if (!$researcher) {
            abort(404);
        }

        $this->service->approveResearcher($id, (int) Auth::id());
        DB::table('users')->where('id', $researcher->user_id)->update(['active' => true]);

        return redirect()->route('research.viewResearcher', $id)
            ->with('success', 'Researcher approved and account activated');
    }

    public function rejectResearcher(Request $request, int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->rejectResearcher($id, (int) Auth::id(), (string) $request->input('reason', ''));

        return redirect()->route('research.researchers')
            ->with('success', 'Researcher registration rejected and archived');
    }

    public function suspendResearcher(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->suspendResearcher($id);

        return redirect()->route('research.viewResearcher', $id)
            ->with('success', 'Researcher suspended');
    }

    // =========================================================================
    // BOOKINGS
    // =========================================================================

    public function bookings(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms();
        $pendingBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.status', 'pending')
            ->select('b.*', 'b.booking_date as date', DB::raw("r.first_name || ' ' || r.last_name as researcher_name"), 'r.email', 'rm.name as room_name')
            ->orderBy('b.booking_date')->get()->toArray();
        $upcomingBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.status', 'confirmed')->where('b.booking_date', '>=', date('Y-m-d'))
            ->select('b.*', 'b.booking_date as date', DB::raw("r.first_name || ' ' || r.last_name as researcher_name"), 'rm.name as room_name')
            ->orderBy('b.booking_date')->limit(20)->get()->toArray();

        return view('research::research.bookings', array_merge(
            $this->getSidebarData('bookings'),
            compact('rooms', 'pendingBookings', 'upcomingBookings')
        ));
    }

    public function book(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher || $researcher->status !== 'approved') {
            return redirect()->route('research.dashboard')->with('error', 'Must be approved researcher');
        }

        $rooms = $this->service->getReadingRooms();

        if ($request->isMethod('post')) {
            $bookingId = $this->service->createBooking([
                'researcher_id'  => (int) $researcher->id,
                'reading_room_id' => (int) $request->input('reading_room_id'),
                'booking_date'   => $request->input('booking_date'),
                'start_time'     => $request->input('start_time'),
                'end_time'       => $request->input('end_time'),
                'purpose'        => $request->input('purpose'),
                'notes'          => $request->input('notes'),
            ]);
            foreach ($request->input('materials', []) as $objectId) {
                $this->service->addMaterialRequest($bookingId, (int) $objectId);
            }

            return redirect()->route('research.viewBooking', $bookingId)
                ->with('success', 'Booking submitted');
        }

        return view('research::research.book', array_merge(
            $this->getSidebarData('book'),
            compact('researcher', 'rooms')
        ));
    }

    public function viewBooking(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $booking = $this->service->getBooking($id);
        if (!$booking) {
            abort(404, 'Booking not found');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('booking_action');
            if ($action === 'confirm') {
                $this->service->confirmBooking($id, (int) Auth::id());
                return redirect()->route('research.viewBooking', $id)->with('success', 'Booking confirmed');
            }
            if ($action === 'cancel') {
                $this->service->cancelBooking($id, 'Cancelled by staff');
                return redirect()->route('research.viewBooking', $id)->with('success', 'Booking cancelled');
            }
            if ($action === 'noshow') {
                $this->service->noShowBooking($id);
                return redirect()->route('research.viewBooking', $id)->with('success', 'Marked as no-show');
            }
        }

        return view('research::research.view-booking', array_merge(
            $this->getSidebarData('bookings'),
            compact('booking')
        ));
    }

    public function confirmBooking(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->confirmBooking($id, (int) Auth::id());

        return redirect()->route('research.viewBooking', $id)->with('success', 'Booking confirmed');
    }

    public function checkInBooking(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->checkIn($id);

        return redirect()->route('research.viewBooking', $id)->with('success', 'Researcher checked in');
    }

    public function checkOutBooking(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->checkOut($id);

        return redirect()->route('research.bookings')->with('success', 'Researcher checked out');
    }

    public function noShowBooking(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->noShowBooking($id);

        return redirect()->route('research.viewBooking', $id)->with('success', 'Marked as no-show');
    }

    public function cancelBooking(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->service->cancelBooking($id, 'Cancelled by staff');

        return redirect()->route('research.viewBooking', $id)->with('success', 'Booking cancelled');
    }

    // =========================================================================
    // WORKSPACE
    // =========================================================================

    public function workspace(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $collections = $this->service->getCollections((int) $researcher->id);
        $savedSearches = $this->service->getSavedSearches((int) $researcher->id);
        $annotations = $this->service->getAnnotations((int) $researcher->id);

        $upcomingBookings = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $researcher->id)
            ->where('b.booking_date', '>=', date('Y-m-d'))
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date')->orderBy('b.start_time')
            ->limit(5)->get()->toArray();

        $pastBookings = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $researcher->id)
            ->where(function ($q) {
                $q->where('b.booking_date', '<', date('Y-m-d'))
                  ->orWhere('b.status', 'completed');
            })
            ->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date', 'desc')->limit(5)->get()->toArray();

        $stats = [
            'total_bookings'       => (int) DB::table('research_booking')->where('researcher_id', $researcher->id)->count(),
            'total_collections'    => count($collections),
            'total_saved_searches' => count($savedSearches),
            'total_annotations'    => count($annotations),
            'total_items'          => (int) DB::table('research_collection_item as ci')
                ->join('research_collection as c', 'ci.collection_id', '=', 'c.id')
                ->where('c.researcher_id', $researcher->id)->count(),
        ];

        if ($request->isMethod('post') && $request->input('booking_action') === 'create_collection') {
            $name = trim((string) $request->input('collection_name'));
            if ($name) {
                $this->service->createCollection((int) $researcher->id, [
                    'name'        => $name,
                    'description' => trim((string) $request->input('collection_description')),
                    'is_public'   => $request->input('is_public') ? true : false,
                ]);

                return redirect()->route('research.workspace')->with('success', 'Collection created successfully.');
            }
        }

        return view('research::research.workspace', array_merge(
            $this->getSidebarData('workspace'),
            compact('researcher', 'collections', 'savedSearches', 'annotations', 'upcomingBookings', 'pastBookings', 'stats')
        ));
    }

    // =========================================================================
    // SAVED SEARCHES
    // =========================================================================

    public function savedSearches(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('booking_action');
            if ($action === 'save') {
                $this->service->saveSearch((int) $researcher->id, [
                    'name'         => $request->input('name'),
                    'search_query' => $request->input('search_query'),
                ]);
            } elseif ($action === 'delete') {
                $this->service->deleteSavedSearch((int) $request->input('id'), (int) $researcher->id);
            }

            return redirect()->route('research.savedSearches');
        }

        $savedSearches = $this->service->getSavedSearches((int) $researcher->id);

        return view('research::research.saved-searches', array_merge(
            $this->getSidebarData('savedSearches'),
            compact('researcher', 'savedSearches')
        ));
    }

    public function storeSavedSearch(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $this->service->saveSearch((int) $researcher->id, [
            'name'         => $request->input('name'),
            'search_query' => $request->input('search_query'),
        ]);

        return redirect()->route('research.savedSearches')->with('success', 'Search saved');
    }

    public function runSavedSearch(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $search = $this->service->getSavedSearch($id);
        if (!$search) {
            abort(404, 'Saved search not found');
        }

        $this->service->runSavedSearch($id);

        return redirect('/informationobject/browse?query=' . urlencode($search->search_query));
    }

    public function destroySavedSearch(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $this->service->deleteSavedSearch($id, (int) $researcher->id);

        return redirect()->route('research.savedSearches')->with('success', 'Saved search deleted');
    }

    // =========================================================================
    // COLLECTIONS
    // =========================================================================

    public function collections(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post') && $request->input('do') === 'create') {
            $id = $this->service->createCollection((int) $researcher->id, [
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
            ]);

            return redirect()->route('research.viewCollection', $id);
        }

        $collections = $this->service->getCollections((int) $researcher->id);

        return view('research::research.collections', array_merge(
            $this->getSidebarData('collections'),
            compact('researcher', 'collections')
        ));
    }

    public function viewCollection(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $collection = $this->service->getCollection($id);
        if (!$collection) {
            abort(404, 'Not found');
        }
        if ((int) $collection->researcher_id !== (int) $researcher->id) {
            return redirect()->route('research.collections')->with('error', 'Access denied');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('booking_action');

            if ($action === 'remove') {
                $this->service->removeFromCollection($id, (int) $request->input('object_id'));
                return redirect()->route('research.viewCollection', $id)->with('success', 'Item removed from collection');
            }
            if ($action === 'add_item') {
                $objectId = (int) $request->input('object_id');
                if ($objectId > 0) {
                    $exists = DB::table('research_collection_item')
                        ->where('collection_id', $id)
                        ->where('object_id', $objectId)->exists();
                    if (!$exists) {
                        $this->service->addToCollection($id, $objectId, trim((string) $request->input('notes', '')));
                        return redirect()->route('research.viewCollection', $id)->with('success', 'Item added to collection');
                    }
                    return redirect()->route('research.viewCollection', $id)->with('error', 'Item already in collection');
                }
            }
            if ($action === 'update_notes') {
                $this->service->updateCollectionItemNotes($id, (int) $request->input('object_id'), trim((string) $request->input('notes')));
                return redirect()->route('research.viewCollection', $id)->with('success', 'Notes updated');
            }
            if ($action === 'update') {
                $name = trim((string) $request->input('name'));
                if ($name) {
                    $this->service->updateCollection($id, [
                        'name'        => $name,
                        'description' => trim((string) $request->input('description')),
                        'is_public'   => $request->input('is_public') ? true : false,
                    ]);
                    return redirect()->route('research.viewCollection', $id)->with('success', 'Collection updated');
                }
            }
            if ($action === 'delete') {
                $this->service->deleteCollection($id);
                return redirect()->route('research.collections')->with('success', 'Collection deleted');
            }
        }

        return view('research::research.view-collection', array_merge(
            $this->getSidebarData('collections'),
            compact('researcher', 'collection')
        ));
    }

    public function storeCollection(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $id = $this->service->createCollection((int) $researcher->id, [
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('research.viewCollection', $id)->with('success', 'Collection created');
    }

    public function destroyCollection(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $this->service->deleteCollection($id);

        return redirect()->route('research.collections')->with('success', 'Collection deleted');
    }

    // =========================================================================
    // ANNOTATIONS
    // =========================================================================

    public function annotations(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('do');

            if ($action === 'delete') {
                $this->service->deleteAnnotation((int) $request->input('id'), (int) $researcher->id);
                return redirect()->route('research.annotations')->with('success', 'Note deleted');
            }
            if ($action === 'create') {
                $content = trim((string) $request->input('content'));
                if ($content) {
                    $this->service->createAnnotation([
                        'researcher_id'  => (int) $researcher->id,
                        'object_id'      => $request->input('object_id'),
                        'entity_type'    => $request->input('entity_type', 'information_object'),
                        'collection_id'  => $request->input('collection_id'),
                        'title'          => $request->input('title'),
                        'content'        => $content,
                        'tags'           => $request->input('tags'),
                        'content_format' => $request->input('content_format', 'text'),
                        'visibility'     => $request->input('visibility', 'private'),
                    ]);
                    return redirect()->route('research.annotations')->with('success', 'Note created');
                }
            }
            if ($action === 'update') {
                $content = trim((string) $request->input('content'));
                if ($content) {
                    $this->service->updateAnnotation((int) $request->input('id'), (int) $researcher->id, [
                        'title'          => $request->input('title'),
                        'content'        => $content,
                        'object_id'      => $request->input('object_id'),
                        'entity_type'    => $request->input('entity_type', 'information_object'),
                        'collection_id'  => $request->input('collection_id'),
                        'tags'           => $request->input('tags'),
                        'content_format' => $request->input('content_format', 'text'),
                        'visibility'     => $request->input('visibility', 'private'),
                    ]);
                    return redirect()->route('research.annotations')->with('success', 'Note updated');
                }
            }
        }

        $q = $request->input('q');
        $annotations = $q
            ? $this->service->searchAnnotations((int) $researcher->id, $q)
            : $this->service->getAnnotations((int) $researcher->id);

        $researchCollections = DB::table('research_collection')
            ->where('researcher_id', $researcher->id)
            ->orderBy('name')->get();

        return view('research::research.annotations', array_merge(
            $this->getSidebarData('annotations'),
            compact('researcher', 'annotations', 'researchCollections')
        ));
    }

    public function storeAnnotation(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $content = trim((string) $request->input('content'));
        if ($content) {
            $this->service->createAnnotation([
                'researcher_id'  => (int) $researcher->id,
                'object_id'      => $request->input('object_id'),
                'entity_type'    => $request->input('entity_type', 'information_object'),
                'collection_id'  => $request->input('collection_id'),
                'title'          => $request->input('title'),
                'content'        => $content,
                'tags'           => $request->input('tags'),
                'content_format' => $request->input('content_format', 'text'),
                'visibility'     => $request->input('visibility', 'private'),
            ]);
            return redirect()->route('research.annotations')->with('success', 'Note created');
        }

        return redirect()->route('research.annotations')->with('error', 'Content is required');
    }

    public function destroyAnnotation(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $this->service->deleteAnnotation($id, (int) $researcher->id);

        return redirect()->route('research.annotations')->with('success', 'Note deleted');
    }

    // =========================================================================
    // JOURNAL
    // =========================================================================

    public function journal(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $filters = [
            'project_id' => $request->input('project_id'),
            'entry_type' => $request->input('entry_type'),
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
            'search'     => $request->input('q'),
        ];

        $query = DB::table('research_journal_entry')
            ->where('researcher_id', $researcher->id);

        if ($filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }
        if ($filters['entry_type']) {
            $query->where('entry_type', $filters['entry_type']);
        }
        if ($filters['date_from']) {
            $query->where('entry_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->where('entry_date', '<=', $filters['date_to']);
        }
        if ($filters['search']) {
            $pattern = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($pattern) {
                $q->where('title', 'ILIKE', $pattern)
                  ->orWhere('content', 'ILIKE', $pattern);
            });
        }

        $entries = $query->orderBy('entry_date', 'desc')->orderBy('created_at', 'desc')->get()->toArray();

        $projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', 'p.id', '=', 'pc.project_id')
            ->where('pc.researcher_id', $researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')
            ->orderBy('p.title')->get()->toArray();

        if ($request->isMethod('post') && $request->input('do') === 'create') {
            $content = $this->service->sanitizeHtml((string) $request->input('content', ''));
            if ($content) {
                DB::table('research_journal_entry')->insert([
                    'researcher_id'      => $researcher->id,
                    'title'              => $request->input('title'),
                    'content'            => $content,
                    'content_format'     => 'html',
                    'project_id'         => $request->input('project_id') ?: null,
                    'entry_type'         => $request->input('entry_type') ?: 'manual',
                    'time_spent_minutes' => $request->input('time_spent_minutes') ?: null,
                    'tags'               => $request->input('tags'),
                    'entry_date'         => $request->input('entry_date') ?: date('Y-m-d'),
                    'created_at'         => now(),
                ]);

                return redirect()->route('research.journal')->with('success', 'Journal entry created');
            }
        }

        return view('research::research.journal', array_merge(
            $this->getSidebarData('journal'),
            compact('researcher', 'entries', 'projects', 'filters')
        ));
    }

    public function journalEntry(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $entry = DB::table('research_journal_entry')->where('id', $id)->first();
        if (!$entry || (int) $entry->researcher_id !== (int) $researcher->id) {
            abort(404);
        }

        $projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', 'p.id', '=', 'pc.project_id')
            ->where('pc.researcher_id', $researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')->orderBy('p.title')->get()->toArray();

        if ($request->isMethod('post')) {
            if ($request->input('form_action') === 'delete') {
                DB::table('research_journal_entry')
                    ->where('id', $id)
                    ->where('researcher_id', $researcher->id)
                    ->delete();

                return redirect()->route('research.journal')->with('success', 'Entry deleted');
            }
            $content = $this->service->sanitizeHtml((string) $request->input('content', ''));
            DB::table('research_journal_entry')->where('id', $id)->where('researcher_id', $researcher->id)->update([
                'title'              => $request->input('title'),
                'content'            => $content,
                'content_format'     => 'html',
                'project_id'         => $request->input('project_id') ?: null,
                'time_spent_minutes' => $request->input('time_spent_minutes') ?: null,
                'tags'               => $request->input('tags'),
                'entry_date'         => $request->input('entry_date') ?: $entry->entry_date,
                'updated_at'         => now(),
            ]);

            return redirect()->route('research.journalEntry', $id)->with('success', 'Entry updated');
        }

        return view('research::research.journal-entry', array_merge(
            $this->getSidebarData('journal'),
            compact('researcher', 'entry', 'projects')
        ));
    }

    // =========================================================================
    // PROJECTS
    // =========================================================================

    public function projects(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $status = $request->input('status');
        $projects = DB::table('research_project as p')
            ->where(function ($q) use ($researcher) {
                $q->where('p.owner_id', $researcher->id)
                  ->orWhereExists(function ($sub) use ($researcher) {
                      $sub->select(DB::raw('1'))
                          ->from('research_project_collaborator')
                          ->whereColumn('research_project_collaborator.project_id', 'p.id')
                          ->where('research_project_collaborator.researcher_id', $researcher->id)
                          ->where('research_project_collaborator.status', 'accepted');
                  });
            });

        if ($status) {
            $projects->where('p.status', $status);
        }
        $projects = $projects->orderBy('p.created_at', 'desc')->get()->toArray();

        if ($request->isMethod('post') && $request->input('form_action') === 'create') {
            $projectId = DB::table('research_project')->insertGetId([
                'owner_id'          => $researcher->id,
                'title'             => $request->input('title'),
                'description'       => $request->input('description'),
                'project_type'      => $request->input('project_type', 'personal'),
                'institution'       => $request->input('institution'),
                'start_date'        => $request->input('start_date') ?: null,
                'expected_end_date' => $request->input('expected_end_date') ?: null,
                'status'            => 'active',
                'created_at'        => now(),
            ]);

            DB::table('research_project_collaborator')->insert([
                'project_id'    => $projectId,
                'researcher_id' => $researcher->id,
                'role'          => 'owner',
                'status'        => 'accepted',
                'invited_at'    => now(),
                'accepted_at'   => now(),
            ]);

            return redirect()->route('research.viewProject', $projectId)->with('success', 'Project created');
        }

        return view('research::research.projects', array_merge(
            $this->getSidebarData('projects'),
            compact('researcher', 'projects', 'status')
        ));
    }

    public function viewProject(int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $project = DB::table('research_project')->where('id', $id)->first();
        if (!$project) {
            abort(404, 'Project not found');
        }

        $collaborators = DB::table('research_project_collaborator as pc')
            ->join('research_researcher as r', 'pc.researcher_id', '=', 'r.id')
            ->where('pc.project_id', $id)
            ->select('pc.*', 'r.first_name', 'r.last_name', 'r.email')
            ->get()->toArray();

        $resources = DB::table('research_project_resource')
            ->where('project_id', $id)
            ->orderBy('added_at', 'desc')
            ->get()->toArray();

        $milestones = DB::table('research_project_milestone')
            ->where('project_id', $id)
            ->orderBy('sort_order')
            ->get()->toArray();

        $activities = DB::table('research_activity_log')
            ->where('project_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()->toArray();

        return view('research::research.view-project', array_merge(
            $this->getSidebarData('projects'),
            compact('researcher', 'project', 'collaborators', 'resources', 'milestones', 'activities')
        ));
    }

    // =========================================================================
    // BIBLIOGRAPHIES
    // =========================================================================

    public function bibliographies(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $bibliographies = DB::table('research_bibliography')
            ->where('researcher_id', $researcher->id)
            ->orderBy('name')
            ->get()->toArray();

        if ($request->isMethod('post') && $request->input('form_action') === 'create') {
            $bibliographyId = DB::table('research_bibliography')->insertGetId([
                'researcher_id'  => $researcher->id,
                'name'           => $request->input('name'),
                'description'    => $request->input('description'),
                'citation_style' => $request->input('citation_style', 'chicago'),
                'created_at'     => now(),
            ]);

            return redirect()->route('research.viewBibliography', $bibliographyId)->with('success', 'Bibliography created');
        }

        return view('research::research.bibliographies', array_merge(
            $this->getSidebarData('bibliographies'),
            compact('researcher', 'bibliographies')
        ));
    }

    public function viewBibliography(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $bibliography = DB::table('research_bibliography')
            ->where('id', $id)
            ->where('researcher_id', $researcher->id)
            ->first();
        if (!$bibliography) {
            abort(404);
        }

        $entries = DB::table('research_bibliography_entry')
            ->where('bibliography_id', $id)
            ->orderBy('sort_order')
            ->get()->toArray();

        if ($request->isMethod('post')) {
            $action = $request->input('form_action');

            if ($action === 'add_entry') {
                $objectId = (int) $request->input('object_id');
                if ($objectId) {
                    $obj = DB::table('information_object_i18n')
                        ->where('id', $objectId)->where('culture', 'en')->first();
                    $maxOrder = (int) (DB::table('research_bibliography_entry')
                        ->where('bibliography_id', $id)->max('sort_order') ?? 0);
                    DB::table('research_bibliography_entry')->insert([
                        'bibliography_id' => $id,
                        'object_id'       => $objectId,
                        'title'           => $obj->title ?? 'Untitled',
                        'sort_order'      => $maxOrder + 1,
                        'created_at'      => now(),
                    ]);

                    return redirect()->route('research.viewBibliography', $id)->with('success', 'Entry added');
                }
            }
            if ($action === 'remove_entry') {
                DB::table('research_bibliography_entry')
                    ->where('id', (int) $request->input('entry_id'))
                    ->where('bibliography_id', $id)
                    ->delete();

                return redirect()->route('research.viewBibliography', $id)->with('success', 'Entry removed');
            }
            if ($action === 'delete') {
                DB::table('research_bibliography_entry')->where('bibliography_id', $id)->delete();
                DB::table('research_bibliography')->where('id', $id)->delete();

                return redirect()->route('research.bibliographies')->with('success', 'Bibliography deleted');
            }
        }

        return view('research::research.view-bibliography', array_merge(
            $this->getSidebarData('bibliographies'),
            compact('researcher', 'bibliography', 'entries')
        ));
    }

    // =========================================================================
    // REPORTS
    // =========================================================================

    public function reports(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $query = DB::table('research_report')
            ->where('researcher_id', $researcher->id);
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }
        $reports = $query->orderBy('created_at', 'desc')->get()->toArray();
        $currentStatus = $request->input('status');

        return view('research::research.reports', array_merge(
            $this->getSidebarData('reports'),
            compact('researcher', 'reports', 'currentStatus')
        ));
    }

    public function viewReport(Request $request, int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $report = DB::table('research_report')->where('id', $id)->first();
        if (!$report || (int) $report->researcher_id !== (int) $researcher->id) {
            abort(404);
        }

        $sections = DB::table('research_report_section')
            ->where('report_id', $id)
            ->orderBy('sort_order')
            ->get()->toArray();
        $report->sections = $sections;

        if ($request->isMethod('post')) {
            $action = $request->input('form_action');

            if ($action === 'add_section') {
                $maxOrder = (int) (DB::table('research_report_section')
                    ->where('report_id', $id)->max('sort_order') ?? -1);
                DB::table('research_report_section')->insert([
                    'report_id'    => $id,
                    'section_type' => $request->input('section_type', 'text'),
                    'title'        => $request->input('title'),
                    'sort_order'   => $maxOrder + 1,
                    'created_at'   => now(),
                ]);

                return redirect()->route('research.viewReport', $id)->with('success', 'Section added');
            }
            if ($action === 'update_section') {
                $content = $this->service->sanitizeHtml((string) $request->input('content', ''));
                DB::table('research_report_section')
                    ->where('id', (int) $request->input('section_id'))
                    ->update([
                        'title'          => $request->input('title'),
                        'content'        => $content,
                        'content_format' => 'html',
                    ]);

                return redirect()->route('research.viewReport', $id)->with('success', 'Section updated');
            }
            if ($action === 'delete_section') {
                DB::table('research_report_section')
                    ->where('id', (int) $request->input('section_id'))
                    ->delete();

                return redirect()->route('research.viewReport', $id)->with('success', 'Section deleted');
            }
            if ($action === 'delete_report') {
                DB::table('research_report_section')->where('report_id', $id)->delete();
                DB::table('research_report')->where('id', $id)->delete();

                return redirect()->route('research.reports')->with('success', 'Report deleted');
            }
            if ($action === 'update_status') {
                DB::table('research_report')->where('id', $id)->update(['status' => $request->input('status')]);

                return redirect()->route('research.viewReport', $id)->with('success', 'Status updated');
            }
        }

        return view('research::research.view-report', array_merge(
            $this->getSidebarData('reports'),
            compact('researcher', 'report')
        ));
    }

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    public function notifications(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $action = $request->input('do');
            if ($action === 'mark_read') {
                $this->service->markNotificationRead((int) $request->input('id'), (int) $researcher->id);
            } elseif ($action === 'mark_all_read') {
                $this->service->markAllNotificationsRead((int) $researcher->id);
            }

            return redirect()->route('research.notifications');
        }

        $notifications = $this->service->getNotifications((int) $researcher->id);

        return view('research::research.notifications', array_merge(
            $this->getSidebarData('notifications'),
            compact('researcher', 'notifications')
        ));
    }

    // =========================================================================
    // ADMIN: READING ROOMS
    // =========================================================================

    public function rooms(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms(false);

        return view('research::research.rooms', array_merge(
            $this->getSidebarData('rooms'),
            compact('rooms')
        ));
    }

    public function editRoom(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $id = (int) $request->input('id');
        $room = $id ? $this->service->getReadingRoom($id) : null;
        $isNew = !$room;

        if ($request->isMethod('post')) {
            $data = [
                'name'                 => $request->input('name'),
                'code'                 => $request->input('code'),
                'location'             => $request->input('location'),
                'capacity'             => (int) $request->input('capacity', 10),
                'description'          => $request->input('description'),
                'amenities'            => $request->input('amenities'),
                'rules'                => $request->input('rules'),
                'opening_time'         => $request->input('opening_time', '09:00:00'),
                'closing_time'         => $request->input('closing_time', '17:00:00'),
                'days_open'            => $request->input('days_open', 'Mon,Tue,Wed,Thu,Fri'),
                'is_active'            => $request->input('is_active') ? true : false,
                'advance_booking_days' => (int) $request->input('advance_booking_days', 14),
                'max_booking_hours'    => (int) $request->input('max_booking_hours', 4),
                'cancellation_hours'   => (int) $request->input('cancellation_hours', 24),
            ];
            if ($id && $room) {
                $this->service->updateReadingRoom($id, $data);
            } else {
                $this->service->createReadingRoom($data);
            }

            return redirect()->route('research.rooms')->with('success', $isNew ? 'Reading room created' : 'Reading room updated');
        }

        return view('research::research.edit-room', array_merge(
            $this->getSidebarData('rooms'),
            compact('room', 'isNew')
        ));
    }

    // =========================================================================
    // ADMIN: SEATS, EQUIPMENT, RETRIEVAL QUEUE, WALK-IN
    // =========================================================================

    public function seats(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms(false);
        $roomId = (int) $request->input('room_id');
        $currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;
        $seats = $roomId ? $this->service->getSeats($roomId) : [];

        return view('research::research.seats', array_merge(
            $this->getSidebarData('seats'),
            compact('rooms', 'roomId', 'currentRoom', 'seats')
        ));
    }

    public function equipment(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms(false);
        $roomId = (int) $request->input('room_id');
        $currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;
        $equipment = $roomId ? $this->service->getEquipment($roomId) : [];

        return view('research::research.equipment', array_merge(
            $this->getSidebarData('equipment'),
            compact('rooms', 'roomId', 'currentRoom', 'equipment')
        ));
    }

    public function retrievalQueue(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms();
        $requests = $this->service->getRetrievalQueue();

        return view('research::research.retrieval-queue', array_merge(
            $this->getSidebarData('retrievalQueue'),
            compact('rooms', 'requests')
        ));
    }

    public function walkIn(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rooms = $this->service->getReadingRooms();
        $roomId = (int) $request->input('room_id');
        $currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;
        $currentWalkIns = $roomId ? $this->service->getCurrentWalkIns($roomId) : [];

        if ($request->isMethod('post')) {
            $action = $request->input('form_action');
            if ($action === 'register') {
                $this->service->registerWalkIn($roomId, $request->all(), (int) Auth::id());

                return redirect()->route('research.walkIn', ['room_id' => $roomId])
                    ->with('success', 'Walk-in visitor registered');
            }
            if ($action === 'checkout') {
                $this->service->checkOutWalkIn((int) $request->input('visitor_id'), (int) Auth::id());

                return redirect()->route('research.walkIn', ['room_id' => $roomId])
                    ->with('success', 'Visitor checked out');
            }
        }

        return view('research::research.walk-in', array_merge(
            $this->getSidebarData('walkIn'),
            compact('rooms', 'roomId', 'currentRoom', 'currentWalkIns')
        ));
    }

    // =========================================================================
    // ADMIN: RESEARCHER TYPES & STATISTICS
    // =========================================================================

    public function adminTypes(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $types = $this->service->getResearcherTypes();

        return view('research::research.admin-types', array_merge(
            $this->getSidebarData('adminTypes'),
            compact('types')
        ));
    }

    public function adminStatistics(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        $stats = $this->service->getAdminStatistics($dateFrom, $dateTo);

        return view('research::research.admin-statistics', array_merge(
            $this->getSidebarData('adminStatistics'),
            compact('stats', 'dateFrom', 'dateTo')
        ));
    }

    public function institutions(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $institutions = $this->service->getInstitutions();

        return view('research::research.institutions', array_merge(
            $this->getSidebarData('institutions'),
            compact('institutions')
        ));
    }

    public function activities(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $activities = $this->service->getRecentActivities();

        return view('research::research.activities', array_merge(
            $this->getSidebarData('activities'),
            compact('activities')
        ));
    }

    // =========================================================================
    // AJAX: SEARCH ITEMS
    // =========================================================================

    public function searchItems(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        $items = $this->service->searchItems($query);

        return response()->json(['items' => $items]);
    }

    // =========================================================================
    // AJAX: ADD TO COLLECTION
    // =========================================================================

    public function addToCollection(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Not authenticated']);
        }

        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher || $researcher->status !== 'approved') {
            return response()->json(['success' => false, 'error' => 'Not an approved researcher']);
        }

        $collectionId = (int) $request->input('collection_id');
        $objectId = (int) $request->input('object_id');
        $notes = (string) $request->input('notes', '');

        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcher->id)
            ->first();
        if (!$collection) {
            return response()->json(['success' => false, 'error' => 'Collection not found']);
        }

        $exists = DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->where('object_id', $objectId)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'error' => 'Item already in collection']);
        }

        $this->service->addToCollection($collectionId, $objectId, $notes);

        return response()->json(['success' => true, 'message' => 'Item added to collection']);
    }

    public function createCollectionAjax(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Not authenticated']);
        }

        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher || $researcher->status !== 'approved') {
            return response()->json(['success' => false, 'error' => 'Not an approved researcher']);
        }

        $name = trim((string) $request->input('name'));
        if (empty($name)) {
            return response()->json(['success' => false, 'error' => 'Collection name is required']);
        }

        $collectionId = $this->service->createCollection((int) $researcher->id, [
            'name'        => $name,
            'description' => trim((string) $request->input('description', '')),
            'is_public'   => $request->input('is_public') ? true : false,
        ]);

        $objectId = (int) $request->input('object_id');
        if ($objectId > 0) {
            $this->service->addToCollection($collectionId, $objectId);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Collection created',
            'collection_id' => $collectionId,
        ]);
    }

    // =========================================================================
    // API KEYS
    // =========================================================================

    public function apiKeys(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher || $researcher->status !== 'approved') {
            return redirect()->route('research.dashboard')->with('error', 'Must be an approved researcher');
        }

        $apiKeys = $this->service->getApiKeys((int) $researcher->id);

        if ($request->isMethod('post')) {
            $action = $request->input('form_action');
            if ($action === 'generate') {
                $result = $this->service->generateApiKey(
                    (int) $researcher->id,
                    trim((string) $request->input('name', 'API Key')),
                    $request->input('permissions', []),
                    $request->input('expires_at') ?: null
                );

                return redirect()->route('research.apiKeys')
                    ->with('success', 'API key generated. Key: <strong>' . $result['key'] . '</strong> - Save this now, it will not be shown again.');
            }
            if ($action === 'revoke') {
                $this->service->revokeApiKey((int) $request->input('key_id'), (int) $researcher->id);

                return redirect()->route('research.apiKeys')->with('success', 'API key revoked');
            }
        }

        return view('research::research.api-keys', array_merge(
            $this->getSidebarData('profile'),
            compact('researcher', 'apiKeys')
        ));
    }

    // =========================================================================
    // RENEWAL
    // =========================================================================

    public function renewal(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if (!in_array($researcher->status, ['expired', 'approved'], true)) {
            return redirect()->route('research.profile')->with('error', 'Renewal not available for your current status');
        }

        if ($request->isMethod('post')) {
            DB::table('access_request')->insert([
                'request_type' => 'researcher',
                'scope_type'   => 'renewal',
                'user_id'      => Auth::id(),
                'reason'       => trim((string) $request->input('reason', '')) ?: 'Researcher registration renewal request',
                'status'       => 'pending',
                'created_at'   => now(),
            ]);

            return redirect()->route('research.profile')
                ->with('success', 'Renewal request submitted. You will be notified when reviewed.');
        }

        return view('research::research.renewal', array_merge(
            $this->getSidebarData('profile'),
            compact('researcher')
        ));
    }

    // =========================================================================
    // TEAM WORKSPACES
    // =========================================================================

    public function workspaces(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $collaborationService = new CollaborationService();

        if ($request->isMethod('post') && $request->input('form_action') === 'create') {
            $collaborationService->createWorkspace((int) $researcher->id, [
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'visibility'  => $request->input('visibility', 'private'),
            ]);

            return redirect()->route('research.workspaces')->with('success', 'Workspace created.');
        }

        $workspaces = $collaborationService->getWorkspaces((int) $researcher->id);

        return view('research::research.workspaces', array_merge(
            $this->getSidebarData('workspaces'),
            compact('workspaces')
        ));
    }

    // =========================================================================
    // VALIDATION QUEUE
    // =========================================================================

    public function validationQueue(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $vqService = new ValidationQueueService();

        $filters = [
            'status'          => $request->input('status', 'pending'),
            'result_type'     => $request->input('result_type'),
            'extraction_type' => $request->input('extraction_type'),
            'min_confidence'  => $request->input('min_confidence'),
        ];

        $page = max(1, (int) $request->input('page', 1));
        $queue = $vqService->getQueue(null, $filters, $page);
        $stats = $vqService->getQueueStats();
        $pendingCount = $vqService->getPendingCount();

        return view('research::research.validation-queue', array_merge(
            $this->getSidebarData('validationQueue'),
            compact('queue', 'stats', 'pendingCount')
        ));
    }

    public function validateResult(Request $request, int $resultId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return response()->json(['error' => 'Not a researcher'], 403);
        }

        $vqService = new ValidationQueueService();
        $action = $request->input('form_action');

        $success = match ($action) {
            'accept' => $vqService->acceptResult($resultId, (int) $researcher->id),
            'reject' => $vqService->rejectResult($resultId, (int) $researcher->id, (string) $request->input('reason', '')),
            'modify' => $vqService->modifyResult($resultId, (int) $researcher->id, $request->input('modified_data', [])),
            default  => false,
        };

        if ($action && !in_array($action, ['accept', 'reject', 'modify'], true)) {
            return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['success' => $success]);
    }

    public function bulkValidate(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return response()->json(['error' => 'Not a researcher'], 403);
        }

        $vqService = new ValidationQueueService();
        $resultIds = $request->input('result_ids', []);
        $action = $request->input('form_action');

        $count = match ($action) {
            'accept' => $vqService->bulkAccept($resultIds, (int) $researcher->id),
            'reject' => $vqService->bulkReject($resultIds, (int) $researcher->id, (string) $request->input('reason', '')),
            default  => 0,
        };

        if ($action && !in_array($action, ['accept', 'reject'], true)) {
            return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    // =========================================================================
    // ENTITY RESOLUTION
    // =========================================================================

    public function entityResolution(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $erService = new EntityResolutionService();

        if ($request->isMethod('post') && $request->input('form_action') === 'propose') {
            $erService->proposeMatch([
                'entity_a_type'     => $request->input('entity_a_type'),
                'entity_a_id'       => (int) $request->input('entity_a_id'),
                'entity_b_type'     => $request->input('entity_b_type'),
                'entity_b_id'       => (int) $request->input('entity_b_id'),
                'relationship_type' => $request->input('relationship_type', 'sameAs'),
                'match_method'      => $request->input('match_method', 'manual'),
                'confidence'        => $request->input('confidence') !== null ? (float) $request->input('confidence') : null,
                'notes'             => $request->input('notes'),
                'proposer_id'       => (int) $researcher->id,
            ]);

            return redirect()->route('research.entityResolution')->with('success', 'Match proposed.');
        }

        $filters = [
            'status'            => $request->input('status'),
            'entity_type'       => $request->input('entity_type'),
            'relationship_type' => $request->input('relationship_type'),
        ];

        $page = max(1, (int) $request->input('page', 1));
        $proposals = $erService->getProposals($filters, $page);

        return view('research::research.entity-resolution', array_merge(
            $this->getSidebarData('entityResolution'),
            compact('proposals')
        ));
    }

    public function resolveEntityResolution(Request $request, int $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return response()->json(['error' => 'Not a researcher'], 403);
        }

        $erService = new EntityResolutionService();
        $success = $erService->resolveMatch($id, (string) $request->input('status'), (int) $researcher->id);

        return response()->json(['success' => $success]);
    }

    public function entityResolutionConflicts(int $id): JsonResponse
    {
        $erService = new EntityResolutionService();
        $conflicts = $erService->getConflictingAssertions($id);

        return response()->json(['conflicts' => $conflicts]);
    }

    // =========================================================================
    // ODRL POLICIES
    // =========================================================================

    public function odrlPolicies(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $odrlService = new OdrlService();

        if ($request->isMethod('post')) {
            $formAction = $request->input('form_action');

            if ($formAction === 'create') {
                $constraintsJson = $request->input('constraints_json');
                if ($constraintsJson) {
                    $decoded = json_decode($constraintsJson, true);
                    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        return redirect()->route('research.odrlPolicies')->with('error', 'Invalid JSON in constraints.');
                    }
                }

                $odrlService->createPolicy([
                    'target_type'      => $request->input('target_type'),
                    'target_id'        => (int) $request->input('target_id'),
                    'policy_type'      => $request->input('policy_type'),
                    'action_type'      => $request->input('action_type'),
                    'constraints_json' => $constraintsJson ?: null,
                    'created_by'       => (int) $researcher->id,
                ]);

                return redirect()->route('research.odrlPolicies')->with('success', 'Policy created.');
            }
            if ($formAction === 'delete') {
                $odrlService->deletePolicy((int) $request->input('policy_id'));

                return redirect()->route('research.odrlPolicies')->with('success', 'Policy deleted.');
            }
        }

        $filters = [
            'target_type' => $request->input('filter_target_type'),
            'policy_type' => $request->input('filter_policy_type'),
            'action_type' => $request->input('filter_action_type'),
        ];

        $page = max(1, (int) $request->input('page', 1));
        $policies = $odrlService->getAllPolicies($filters, 25, ($page - 1) * 25);

        return view('research::research.odrl-policies', array_merge(
            $this->getSidebarData('odrlPolicies'),
            compact('policies')
        ));
    }

    // =========================================================================
    // DOCUMENT TEMPLATES
    // =========================================================================

    public function documentTemplates(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        if ($request->isMethod('post')) {
            $formAction = $request->input('form_action');

            if ($formAction === 'create') {
                DB::table('research_document_template')->insert([
                    'name'          => $request->input('name'),
                    'document_type' => $request->input('document_type'),
                    'description'   => $request->input('description'),
                    'fields_json'   => $request->input('fields_json') ?: '[]',
                    'created_by'    => $researcher->id,
                    'created_at'    => now(),
                ]);

                return redirect()->route('research.documentTemplates')->with('success', 'Template created.');
            }
            if ($formAction === 'update') {
                $templateId = (int) $request->input('template_id');
                DB::table('research_document_template')
                    ->where('id', $templateId)
                    ->update([
                        'name'          => $request->input('name'),
                        'document_type' => $request->input('document_type'),
                        'description'   => $request->input('description'),
                        'fields_json'   => $request->input('fields_json') ?: '[]',
                    ]);

                return redirect()->route('research.documentTemplates')->with('success', 'Template updated.');
            }
        }

        $templates = DB::table('research_document_template')
            ->orderBy('name')
            ->get()
            ->toArray();

        return view('research::research.document-templates', array_merge(
            $this->getSidebarData('documentTemplates'),
            compact('templates')
        ));
    }

    // =========================================================================
    // REPRODUCTIONS
    // =========================================================================

    public function reproductions(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $researcher = $this->service->getResearcherByUserId((int) Auth::id());
        if (!$researcher) {
            return redirect()->route('researcher.register');
        }

        $query = DB::table('research_reproduction_request')
            ->where('researcher_id', $researcher->id);
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }
        $requests = $query->orderBy('created_at', 'desc')->get()->toArray();

        return view('research::research.reproductions', array_merge(
            $this->getSidebarData('reproductions'),
            compact('researcher', 'requests')
        ));
    }
}
