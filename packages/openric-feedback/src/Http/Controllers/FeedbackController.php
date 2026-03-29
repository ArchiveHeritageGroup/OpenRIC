<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Feedback controller -- full adaptation of Heratio AhgFeedback\Controllers\FeedbackController (316 lines).
 *
 * Heratio's controller handled:
 *   - general(): public submission form (GET/POST), loads feedback types from taxonomy
 *   - browse(): admin listing with status filter, sort, sidebar counts, SimplePager
 *   - edit(): admin view of single feedback with readonly fields + editable status/notes
 *   - update(): admin POST to change status, completed_at, admin_notes
 *   - destroy(): admin delete (feedback_i18n + feedback + object)
 *   - view(): read-only detail view
 *   - submitSuccess(): thank-you page
 *
 * OpenRiC replicates all seven actions with Blade views. DB logic is delegated to
 * FeedbackService. Views use Bootstrap 5 and theme::layouts.1col.
 */
class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackServiceInterface $service,
    ) {}

    /**
     * Public: general feedback submission form (GET shows form, POST submits).
     *
     * Adapted from Heratio general() which loads feedback types from term/taxonomy
     * tables with culture joins and falls back to hardcoded defaults. OpenRiC uses
     * the service's getCategories() method. POST validation mirrors Heratio's rules
     * for subject, remarks, feed_name, feed_surname, feed_email.
     */
    public function general(Request $request, ?string $slug = null): View|RedirectResponse
    {
        $categories = $this->service->getCategories();

        if ($request->isMethod('post')) {
            $request->validate([
                'subject'           => 'required|string|max:1024',
                'message'           => 'required|string|max:10000',
                'category'          => 'required|string|in:general,bug,feature,content,compliment,usability',
                'feed_name'         => 'required|string|max:100',
                'feed_surname'      => 'required|string|max:100',
                'feed_email'        => 'required|email|max:255',
                'feed_phone'        => 'nullable|string|max:50',
                'feed_relationship' => 'nullable|string|max:255',
                'rating'            => 'nullable|integer|min:1|max:5',
            ]);

            $this->service->submit([
                'user_id'           => Auth::id(),
                'subject'           => $request->input('subject'),
                'category'          => $request->input('category'),
                'message'           => $request->input('message'),
                'rating'            => $request->input('rating'),
                'url'               => $slug,
                'feed_name'         => $request->input('feed_name'),
                'feed_surname'      => $request->input('feed_surname'),
                'feed_email'        => $request->input('feed_email'),
                'feed_phone'        => $request->input('feed_phone', ''),
                'feed_relationship' => $request->input('feed_relationship', ''),
                'user_agent'        => $request->userAgent(),
                'ip_address'        => $request->ip(),
            ]);

            return redirect()->route('feedback.submit-success')
                ->with('success', 'Thank you for your feedback. We will review it shortly.');
        }

        return view('openric-feedback::general', [
            'categories' => $categories,
            'slug'       => $slug,
        ]);
    }

    /**
     * Admin: browse all feedback with filters and sorting.
     *
     * Adapted from Heratio browse() which builds a query joining feedback + feedback_i18n
     * + object, filters by status (all/pending/completed), sorts by name/date direction,
     * computes sidebar counts (totalCount, pendingCount, completedCount), and paginates
     * with SimplePager. OpenRiC delegates to FeedbackService::browse() + getStats().
     */
    public function browse(Request $request): View
    {
        $params = [
            'page'   => $request->input('page', 1),
            'limit'  => 30,
            'status' => $request->input('status', 'all'),
            'sort'   => $request->input('sort', 'dateDown'),
            'search' => $request->input('search'),
        ];

        $data  = $this->service->browse($params);
        $stats = $this->service->getStats();

        return view('openric-feedback::browse', [
            'results'        => $data['results'],
            'total'          => $data['total'],
            'page'           => $data['page'],
            'lastPage'       => $data['lastPage'],
            'limit'          => $data['limit'],
            'status'         => $params['status'],
            'sort'           => $params['sort'],
            'search'         => $params['search'],
            'totalCount'     => $stats['total'],
            'pendingCount'   => $stats['pending'] + $stats['new'],
            'completedCount' => $stats['completed'],
            'reviewedCount'  => $stats['reviewed'],
            'avgRating'      => $stats['avg_rating'],
        ]);
    }

    /**
     * Admin: edit a single feedback item.
     *
     * Adapted from Heratio edit() which joins feedback + feedback_i18n on culture
     * and selects all columns for display. Shows readonly submission data and
     * editable admin fields (status, admin_notes, completed_at).
     */
    public function edit(int $id): View
    {
        $feedback = $this->service->find($id);

        if (!$feedback) {
            abort(404, 'Feedback not found.');
        }

        $categories = $this->service->getCategories();

        return view('openric-feedback::edit', [
            'feedback'   => $feedback,
            'categories' => $categories,
        ]);
    }

    /**
     * Admin: update feedback status and admin notes.
     *
     * Adapted from Heratio update() which validates status (pending/completed),
     * manages completed_at timestamp (auto-fills on completed, clears on pending),
     * and stores admin notes in the unique_identifier column (repurposed).
     * OpenRiC uses dedicated admin_notes and completed_at columns.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status'      => 'required|in:pending,new,reviewed,completed,closed',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $this->service->updateStatus(
            $id,
            $request->input('status'),
            (int) Auth::id(),
            $request->input('admin_notes', ''),
        );

        return redirect()->route('feedback.browse')
            ->with('success', 'Feedback updated successfully.');
    }

    /**
     * Admin: delete a feedback item.
     *
     * Adapted from Heratio destroy() which deletes from feedback_i18n (all cultures),
     * feedback, and object tables in sequence. OpenRiC deletes the single feedback row.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->service->delete($id);

        return redirect()->route('feedback.browse')
            ->with('success', 'Feedback deleted successfully.');
    }

    /**
     * Admin/Public: view a single feedback entry (read-only).
     *
     * Adapted from Heratio view() which joins feedback + feedback_i18n on culture
     * and displays all fields in a detail layout.
     */
    public function view(int $id): View
    {
        $feedback = $this->service->find($id);

        if (!$feedback) {
            abort(404, 'Feedback not found.');
        }

        return view('openric-feedback::view', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * Public: thank-you page after submission.
     *
     * Mirrors Heratio submitSuccess().
     */
    public function submitSuccess(): View
    {
        return view('openric-feedback::submit');
    }

    /**
     * Admin: export all feedback as CSV.
     *
     * OpenRiC extension (not in Heratio). Downloads a CSV file with all feedback
     * entries including contact details and admin notes.
     */
    public function export(): StreamedResponse
    {
        return $this->service->export();
    }
}
