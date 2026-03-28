<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Feedback service -- adapted from Heratio AhgFeedback\Controllers\FeedbackController (316 lines).
 */
class FeedbackService implements FeedbackServiceInterface
{
    public function submit(array $data): int
    {
        return DB::table('feedback')->insertGetId([
            'uuid'        => (string) Str::uuid(),
            'user_id'     => $data['user_id'] ?? null,
            'category'    => $data['category'] ?? 'general',
            'rating'      => isset($data['rating']) ? (int) $data['rating'] : null,
            'message'     => $data['message'],
            'url'         => $data['url'] ?? null,
            'user_agent'  => $data['user_agent'] ?? null,
            'ip_address'  => $data['ip_address'] ?? null,
            'status'      => 'new',
            'reviewed_by' => null,
            'admin_notes' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function browse(array $params = []): array
    {
        $page     = max(1, (int) ($params['page'] ?? 1));
        $limit    = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $category = $params['category'] ?? null;
        $status   = $params['status'] ?? null;
        $sort     = $params['sort'] ?? 'created_at';
        $sortDir  = strtolower($params['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $q = DB::table('feedback')
            ->leftJoin('users', 'feedback.user_id', '=', 'users.id')
            ->select('feedback.*', 'users.name as user_name', 'users.email as user_email');

        if ($category) {
            $q->where('feedback.category', $category);
        }

        if ($status) {
            $q->where('feedback.status', $status);
        }

        $sortCol = match ($sort) {
            'category' => 'feedback.category',
            'status'   => 'feedback.status',
            'rating'   => 'feedback.rating',
            default    => 'feedback.created_at',
        };

        $total = $q->count();

        $results = $q->orderBy($sortCol, $sortDir)
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

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

    public function updateStatus(int $id, string $status, ?int $reviewerId = null, string $adminNotes = ''): bool
    {
        $validStatuses = ['new', 'reviewed', 'resolved', 'closed'];

        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $data = [
            'status'     => $status,
            'updated_at' => now(),
        ];

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

    public function getStats(): array
    {
        $row = DB::table('feedback')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count")
            ->selectRaw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw("AVG(rating) as avg_rating")
            ->first();

        return [
            'total'      => (int) $row->total,
            'new'        => (int) $row->new_count,
            'reviewed'   => (int) $row->reviewed,
            'resolved'   => (int) $row->resolved,
            'closed'     => (int) $row->closed,
            'avg_rating' => $row->avg_rating !== null ? round((float) $row->avg_rating, 2) : null,
        ];
    }

    public function export(): StreamedResponse
    {
        $items = DB::table('feedback')
            ->leftJoin('users', 'feedback.user_id', '=', 'users.id')
            ->select('feedback.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('feedback.created_at')
            ->get();

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID', 'UUID', 'Category', 'Rating', 'Message', 'URL',
                'Status', 'User', 'Email', 'Admin Notes', 'Created At',
            ]);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->id,
                    $item->uuid,
                    $item->category,
                    $item->rating ?? '',
                    $item->message,
                    $item->url ?? '',
                    $item->status,
                    $item->user_name ?? 'Anonymous',
                    $item->user_email ?? '',
                    $item->admin_notes ?? '',
                    $item->created_at,
                ]);
            }
            fclose($out);
        }, 'feedback-export-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
