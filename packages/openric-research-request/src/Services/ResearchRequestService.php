<?php

declare(strict_types=1);

namespace OpenRiC\ResearchRequest\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\ResearchRequest\Contracts\ResearchRequestServiceInterface;

/**
 * Research request service -- adapted from Heratio AhgCart\Services\CartService (97 lines)
 * and AhgCart\Controllers\CartController checkout flow.
 *
 * Replaces Heratio's e-commerce cart with a research-request workflow:
 * user adds entities to cart -> submits request with purpose -> admin approves/denies.
 */
class ResearchRequestService implements ResearchRequestServiceInterface
{
    public function addToCart(int $userId, string $entityIri, string $entityType, string $title): bool
    {
        $exists = DB::table('research_cart_items')
            ->where('user_id', $userId)
            ->where('entity_iri', $entityIri)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table('research_cart_items')->insert([
            'user_id'     => $userId,
            'entity_iri'  => $entityIri,
            'entity_type' => $entityType,
            'title'       => $title,
            'added_at'    => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return true;
    }

    public function removeFromCart(int $userId, int $cartItemId): bool
    {
        return DB::table('research_cart_items')
            ->where('id', $cartItemId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getCart(int $userId): Collection
    {
        return DB::table('research_cart_items')
            ->where('user_id', $userId)
            ->orderByDesc('added_at')
            ->get();
    }

    public function submitRequest(int $userId, string $purpose, string $notes = ''): string
    {
        $cartItems = $this->getCart($userId);

        if ($cartItems->isEmpty()) {
            throw new \RuntimeException('Cart is empty -- cannot submit request.');
        }

        $uuid = (string) Str::uuid();

        $itemsJson = $cartItems->map(fn (object $item): array => [
            'entity_iri'  => $item->entity_iri,
            'entity_type' => $item->entity_type,
            'title'       => $item->title,
        ])->values()->toArray();

        DB::table('research_requests')->insert([
            'uuid'        => $uuid,
            'user_id'     => $userId,
            'purpose'     => $purpose,
            'status'      => 'pending',
            'items'       => json_encode($itemsJson, JSON_THROW_ON_ERROR),
            'notes'       => $notes,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('research_cart_items')->where('user_id', $userId)->delete();

        return $uuid;
    }

    public function getRequests(array $params = []): array
    {
        $page   = max(1, (int) ($params['page'] ?? 1));
        $limit  = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $status = $params['status'] ?? null;
        $userId = $params['user_id'] ?? null;

        $q = DB::table('research_requests')
            ->leftJoin('users', 'research_requests.user_id', '=', 'users.id')
            ->select('research_requests.*', 'users.name as user_name', 'users.email as user_email');

        if ($status) {
            $q->where('research_requests.status', $status);
        }

        if ($userId) {
            $q->where('research_requests.user_id', $userId);
        }

        $total = $q->count();

        $results = $q->orderByDesc('research_requests.created_at')
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

    public function getRequest(int $requestId): ?object
    {
        return DB::table('research_requests')
            ->leftJoin('users', 'research_requests.user_id', '=', 'users.id')
            ->leftJoin('users as reviewer', 'research_requests.reviewed_by', '=', 'reviewer.id')
            ->select(
                'research_requests.*',
                'users.name as user_name',
                'users.email as user_email',
                'reviewer.name as reviewer_name',
            )
            ->where('research_requests.id', $requestId)
            ->first();
    }

    public function approveRequest(int $requestId, int $reviewerId, string $notes = ''): bool
    {
        $updated = DB::table('research_requests')
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'notes'       => DB::raw("CONCAT(COALESCE(notes, ''), '\n[Admin] " . addslashes($notes) . "')"),
                'updated_at'  => now(),
            ]);

        return $updated > 0;
    }

    public function denyRequest(int $requestId, int $reviewerId, string $notes = ''): bool
    {
        $updated = DB::table('research_requests')
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'denied',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'notes'       => DB::raw("CONCAT(COALESCE(notes, ''), '\n[Admin] " . addslashes($notes) . "')"),
                'updated_at'  => now(),
            ]);

        return $updated > 0;
    }

    public function getStats(): array
    {
        $counts = DB::table('research_requests')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->first();

        return [
            'total'     => (int) $counts->total,
            'pending'   => (int) $counts->pending,
            'approved'  => (int) $counts->approved,
            'denied'    => (int) $counts->denied,
            'completed' => (int) $counts->completed,
        ];
    }
}
