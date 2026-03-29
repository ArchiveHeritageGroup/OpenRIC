<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Feedback service implementation.
 *
 * Adapted from Heratio AhgFeedback\Controllers\FeedbackController (316 lines).
 * Heratio embedded all DB queries directly in controller methods using the
 * AtoM object/feedback/feedback_i18n triple-table pattern with culture joins.
 * OpenRiC uses a single PostgreSQL feedback table and extracts all query logic here.
 */
class FeedbackService implements FeedbackServiceInterface
{
    /** Valid status values mirroring Heratio's pending/completed plus OpenRiC extensions. */
    private const VALID_STATUSES = ['pending', 'new', 'reviewed', 'completed', 'closed'];

    /**
     * {@inheritDoc}
     *
     * Heratio's general() POST handler creates rows in object, feedback, and feedback_i18n.
     * OpenRiC inserts a single feedback row with all fields.
     */
    public function submit(array $data): int
    {
        return DB::table('feedback')->insertGetId([
            'uuid'              => (string) Str::uuid(),
            'user_id'           => $data['user_id'] ?? null,
            'subject'           => $data['subject'] ?? '',
            'category'          => $data['category'] ?? 'general',
            'message'           => $data['message'] ?? '',
            'rating'            => isset($data['rating']) ? (int) $data['rating'] : null,
            'url'               => $data['url'] ?? null,
            'feed_name'         => $data['feed_name'] ?? '',
            'feed_surname'      => $data['feed_surname'] ?? '',
            'feed_email'        => $data['feed_email'] ?? '',
            'feed_phone'        => $data['feed_phone'] ?? '',
            'feed_relationship' => $data['feed_relationship'] ?? '',
            'user_agent'        => $data['user_agent'] ?? null,
            'ip_address'        => $data['ip_address'] ?? null,
            'status'            => 'pending',
            'reviewed_by'       => null,
            'admin_notes'       => null,
            'completed_at'      => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * Heratio's browse() builds a query joining feedback + feedback_i18n + object,
     * filters by status (all/pending/completed), sorts by name or date direction,
     * paginates with SimplePager, and returns sidebar counts. OpenRiC replicates
     * all filter/sort/paginate logic against the single feedback table.
     */
    public function browse(array $params = []): array
    {
        $page    = max(1, (int) ($params['page'] ?? 1));
        $limit   = min(100, max(1, (int) ($params['limit'] ?? 30)));
        $status  = $params['status'] ?? 'all';
        $sort    = $params['sort'] ?? 'dateDown';
        $search  = $params['search'] ?? null;

        $query = DB::table('feedback')
            ->leftJoin('users', 'feedback.user_id', '=', 'users.id')
            ->select(
                'feedback.id',
                'feedback.uuid',
                'feedback.subject',
                'feedback.category',
                'feedback.message',
                'feedback.rating',
                'feedback.url',
                'feedback.feed_name',
                'feedback.feed_surname',
                'feedback.feed_email',
                'feedback.feed_phone',
                'feedback.feed_relationship',
                'feedback.status',
                'feedback.admin_notes',
                'feedback.completed_at',
                'feedback.created_at',
                'feedback.updated_at',
                'users.name as user_name',
                'users.email as user_email',
            );

        // Status filter — mirrors Heratio's pending/completed filter
        if ($status === 'pending') {
            $query->where('feedback.status', '=', 'pending');
        } elseif ($status === 'completed') {
            $query->where('feedback.status', '=', 'completed');
        } elseif ($status === 'new') {
            $query->where('feedback.status', '=', 'new');
        } elseif ($status === 'reviewed') {
            $query->where('feedback.status', '=', 'reviewed');
        }
        // 'all' => no filter

        // Search filter (OpenRiC extension beyond Heratio)
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('feedback.subject', 'ILIKE', $term)
                  ->orWhere('feedback.message', 'ILIKE', $term)
                  ->orWhere('feedback.feed_name', 'ILIKE', $term)
                  ->orWhere('feedback.feed_surname', 'ILIKE', $term)
                  ->orWhere('feedback.feed_email', 'ILIKE', $term);
            });
        }

        // Sort — mirrors Heratio's nameUp/nameDown/dateUp/dateDown switch
        switch ($sort) {
            case 'nameUp':
                $query->orderBy('feedback.subject', 'asc');
                break;
            case 'nameDown':
                $query->orderBy('feedback.subject', 'desc');
                break;
            case 'dateUp':
                $query->orderBy('feedback.created_at', 'asc');
                break;
            case 'dateDown':
            default:
                $query->orderBy('feedback.created_at', 'desc');
                break;
        }

        // Total for current filters (before pagination)
        $total = (clone $query)->count();

        // Paginate
        $offset  = ($page - 1) * $limit;
        $results = $query->offset($offset)->limit($limit)->get()
            ->map(fn (object $row): array => (array) $row)
            ->toArray();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Heratio's edit() joins feedback + feedback_i18n on culture and selects all columns.
     * OpenRiC joins feedback + users + reviewer.
     */
    public function find(int $id): ?object
    {
        return DB::table('feedback')
            ->leftJoin('users', 'feedback.user_id', '=', 'users.id')
            ->leftJoin('users as reviewer', 'feedback.reviewed_by', '=', 'reviewer.id')
            ->select(
                'feedback.*',
                'users.name as user_name',
                'users.email as user_email',
                'reviewer.name as reviewer_name',
            )
            ->where('feedback.id', $id)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Heratio's update() sets status, status_id, completed_at, and repurposes
     * unique_identifier for admin notes. Auto-fills completed_at when status
     * becomes 'completed'. Clears completed_at when reverted to 'pending'.
     */
    public function updateStatus(int $id, string $status, ?int $reviewerId = null, string $adminNotes = ''): bool
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        $data = [
            'status'     => $status,
            'updated_at' => now(),
        ];

        // Auto-manage completed_at — mirrors Heratio's completed_at logic
        if ($status === 'completed') {
            $data['completed_at'] = now();
        } elseif ($status === 'pending' || $status === 'new') {
            $data['completed_at'] = null;
        }

        if ($reviewerId !== null) {
            $data['reviewed_by'] = $reviewerId;
        }

        if ($adminNotes !== '') {
            $data['admin_notes'] = $adminNotes;
        }

        return DB::table('feedback')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     *
     * Heratio's destroy() deletes from feedback_i18n, feedback, and object tables
     * (three deletes). OpenRiC deletes the single feedback row.
     */
    public function delete(int $id): bool
    {
        return DB::table('feedback')->where('id', $id)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     *
     * Heratio computes totalCount, pendingCount, completedCount as three separate
     * COUNT queries in browse(). OpenRiC uses a single query with conditional aggregation.
     */
    public function getStats(): array
    {
        $row = DB::table('feedback')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count")
            ->selectRaw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw('AVG(rating) as avg_rating')
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'pending'    => (int) ($row->pending ?? 0),
            'new'        => (int) ($row->new_count ?? 0),
            'reviewed'   => (int) ($row->reviewed ?? 0),
            'completed'  => (int) ($row->completed ?? 0),
            'closed'     => (int) ($row->closed ?? 0),
            'avg_rating' => $row->avg_rating !== null ? round((float) $row->avg_rating, 2) : null,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Heratio has no export feature. This is an OpenRiC extension providing
     * CSV download of all feedback entries with contact and admin data.
     */
    public function getCategories(): array
    {
        return [
            ['id' => 'general',    'name' => 'General feedback'],
            ['id' => 'bug',        'name' => 'Bug report'],
            ['id' => 'feature',    'name' => 'Feature request'],
            ['id' => 'content',    'name' => 'Content correction'],
            ['id' => 'compliment', 'name' => 'Compliment'],
            ['id' => 'usability',  'name' => 'Usability issue'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function export(): StreamedResponse
    {
        $items = DB::table('feedback')
            ->leftJoin('users', 'feedback.user_id', '=', 'users.id')
            ->select('feedback.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('feedback.created_at')
            ->get();

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, [
                'ID', 'UUID', 'Subject', 'Category', 'Rating', 'Message',
                'Name', 'Surname', 'Email', 'Phone', 'Relationship',
                'Related URL', 'Status', 'Admin Notes', 'Completed At',
                'User', 'User Email', 'Created At', 'Updated At',
            ]);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->id,
                    $item->uuid,
                    $item->subject,
                    $item->category,
                    $item->rating ?? '',
                    $item->message,
                    $item->feed_name,
                    $item->feed_surname,
                    $item->feed_email,
                    $item->feed_phone,
                    $item->feed_relationship,
                    $item->url ?? '',
                    $item->status,
                    $item->admin_notes ?? '',
                    $item->completed_at ?? '',
                    $item->user_name ?? 'Anonymous',
                    $item->user_email ?? '',
                    $item->created_at,
                    $item->updated_at,
                ]);
            }
            fclose($out);
        }, 'feedback-export-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
