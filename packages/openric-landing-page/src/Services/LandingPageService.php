<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;

/**
 * Landing page service -- adapted from Heratio AhgLandingPage\Services\LandingPageService.
 *
 * Full block-based page builder with CRUD for pages, blocks, block types, versioning,
 * user dashboards, and nested column layouts. PostgreSQL storage via query builder.
 */
class LandingPageService implements LandingPageServiceInterface
{
    // ── Page CRUD ────────────────────────────────────────────────────────

    public function getAllPages(): Collection
    {
        return DB::table('openric_landing_page as p')
            ->leftJoin(DB::raw('(SELECT page_id, COUNT(*) as block_count FROM openric_landing_block GROUP BY page_id) as bc'), 'p.id', '=', 'bc.page_id')
            ->select('p.*', DB::raw('COALESCE(bc.block_count, 0) as block_count'))
            ->orderBy('p.name')
            ->get();
    }

    public function getPage(int $id): ?object
    {
        return DB::table('openric_landing_page')->where('id', $id)->first();
    }

    public function getPageBySlug(?string $slug): ?object
    {
        if ($slug) {
            return DB::table('openric_landing_page')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();
        }

        return DB::table('openric_landing_page')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function createPage(array $data, int $userId): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? 'page');
        }

        $exists = DB::table('openric_landing_page')
            ->where('slug', $data['slug'])
            ->exists();

        if ($exists) {
            return ['success' => false, 'error' => 'Slug already exists'];
        }

        // If setting as default, clear existing defaults of same type
        if (!empty($data['is_default'])) {
            $pageType = $data['page_type'] ?? 'landing';
            DB::table('openric_landing_page')
                ->where('page_type', $pageType)
                ->update(['is_default' => false]);
        }

        $data['created_by'] = $userId;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (!isset($data['page_type'])) {
            $data['page_type'] = 'landing';
        }

        $id = DB::table('openric_landing_page')->insertGetId($data);

        return ['success' => true, 'page_id' => $id];
    }

    public function updatePage(int $id, array $data, int $userId): array
    {
        // If setting as default, clear existing defaults of same type
        if (!empty($data['is_default'])) {
            $page = $this->getPage($id);
            if ($page) {
                DB::table('openric_landing_page')
                    ->where('page_type', $page->page_type ?? 'landing')
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }
        }

        $data['updated_by'] = $userId;
        $data['updated_at'] = now();

        DB::table('openric_landing_page')->where('id', $id)->update($data);

        return ['success' => true];
    }

    public function deletePage(int $id, int $userId): array
    {
        // Delete child blocks first (nested in columns)
        $blockIds = DB::table('openric_landing_block')
            ->where('page_id', $id)
            ->pluck('id');

        if ($blockIds->isNotEmpty()) {
            DB::table('openric_landing_block')
                ->whereIn('parent_block_id', $blockIds)
                ->delete();
        }

        DB::table('openric_landing_block')->where('page_id', $id)->delete();
        DB::table('openric_landing_page_version')->where('page_id', $id)->delete();
        DB::table('openric_landing_page')->where('id', $id)->delete();

        return ['success' => true];
    }

    // ── Block CRUD ───────────────────────────────────────────────────────

    public function getPageBlocks(int $pageId, bool $visibleOnly = true): Collection
    {
        $query = DB::table('openric_landing_block as b')
            ->leftJoin('openric_landing_block_type as bt', 'b.block_type_id', '=', 'bt.id')
            ->where('b.page_id', $pageId)
            ->whereNull('b.parent_block_id')
            ->select(
                'b.*',
                'bt.label as type_label',
                'bt.icon as type_icon',
                'bt.machine_name',
                'bt.config_schema',
                'bt.default_config'
            );

        if ($visibleOnly) {
            $query->where('b.is_visible', true);
        }

        $blocks = $query->orderBy('b.position')->get();

        // Attach child blocks for column layouts
        $blockIds = $blocks->pluck('id')->toArray();
        if (!empty($blockIds)) {
            $children = DB::table('openric_landing_block as b')
                ->leftJoin('openric_landing_block_type as bt', 'b.block_type_id', '=', 'bt.id')
                ->whereIn('b.parent_block_id', $blockIds)
                ->select(
                    'b.*',
                    'bt.label as type_label',
                    'bt.icon as type_icon',
                    'bt.machine_name',
                    'bt.config_schema',
                    'bt.default_config'
                )
                ->orderBy('b.position')
                ->get();

            $childMap = [];
            foreach ($children as $child) {
                $parentId = $child->parent_block_id;
                if (!isset($childMap[$parentId])) {
                    $childMap[$parentId] = [];
                }
                $childMap[$parentId][] = $child;
            }

            foreach ($blocks as $block) {
                $block->child_blocks = $childMap[$block->id] ?? [];
            }
        }

        return $blocks;
    }

    public function getChildBlocks(int $parentBlockId, bool $visibleOnly = true): Collection
    {
        $query = DB::table('openric_landing_block as b')
            ->leftJoin('openric_landing_block_type as bt', 'b.block_type_id', '=', 'bt.id')
            ->where('b.parent_block_id', $parentBlockId)
            ->select(
                'b.*',
                'bt.label as type_label',
                'bt.icon as type_icon',
                'bt.machine_name',
                'bt.config_schema',
                'bt.default_config'
            );

        if ($visibleOnly) {
            $query->where('b.is_visible', true);
        }

        return $query->orderBy('b.position')->get();
    }

    public function getBlockTypes(): Collection
    {
        return DB::table('openric_landing_block_type')
            ->orderBy('label')
            ->get();
    }

    public function addBlock(int $pageId, int $blockTypeId, array $config, int $userId, array $options = []): array
    {
        $parentId = $options['parent_block_id'] ?? null;

        $maxPos = DB::table('openric_landing_block')
            ->where('page_id', $pageId)
            ->when($parentId, fn ($q) => $q->where('parent_block_id', $parentId))
            ->when(!$parentId, fn ($q) => $q->whereNull('parent_block_id'))
            ->max('position') ?? 0;

        $data = [
            'page_id'       => $pageId,
            'block_type_id' => $blockTypeId,
            'config'        => json_encode($config, JSON_THROW_ON_ERROR),
            'position'      => $maxPos + 1,
            'is_visible'    => true,
            'created_by'    => $userId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        if ($parentId) {
            $data['parent_block_id'] = (int) $parentId;
            $data['column_slot']     = $options['column_slot'] ?? null;
        }

        $id = DB::table('openric_landing_block')->insertGetId($data);

        return ['success' => true, 'block_id' => $id];
    }

    public function updateBlock(int $blockId, array $data, int $userId): array
    {
        if (isset($data['config']) && is_array($data['config'])) {
            $data['config'] = json_encode($data['config'], JSON_THROW_ON_ERROR);
        }

        $data['updated_by'] = $userId;
        $data['updated_at'] = now();

        DB::table('openric_landing_block')->where('id', $blockId)->update($data);

        return ['success' => true];
    }

    public function deleteBlock(int $blockId, int $userId): array
    {
        // Delete child blocks (nested in columns)
        DB::table('openric_landing_block')
            ->where('parent_block_id', $blockId)
            ->delete();

        DB::table('openric_landing_block')
            ->where('id', $blockId)
            ->delete();

        return ['success' => true];
    }

    public function reorderBlocks(int $pageId, array $order, int $userId): array
    {
        foreach ($order as $item) {
            DB::table('openric_landing_block')
                ->where('id', (int) $item['id'])
                ->where('page_id', $pageId)
                ->update([
                    'position'   => (int) $item['position'],
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        }

        return ['success' => true];
    }

    public function duplicateBlock(int $blockId, int $userId): array
    {
        $block = DB::table('openric_landing_block')->where('id', $blockId)->first();

        if (!$block) {
            return ['success' => false, 'error' => 'Block not found'];
        }

        $newId = DB::table('openric_landing_block')->insertGetId([
            'page_id'         => $block->page_id,
            'block_type_id'   => $block->block_type_id,
            'parent_block_id' => $block->parent_block_id,
            'column_slot'     => $block->column_slot,
            'config'          => $block->config,
            'title'           => ($block->title ?? '') . ' (copy)',
            'css_classes'     => $block->css_classes ?? null,
            'container_type'  => $block->container_type ?? 'container',
            'background_color' => $block->background_color ?? null,
            'text_color'      => $block->text_color ?? null,
            'padding_top'     => $block->padding_top ?? '3',
            'padding_bottom'  => $block->padding_bottom ?? '3',
            'col_span'        => $block->col_span ?? 12,
            'position'        => $block->position + 1,
            'is_visible'      => $block->is_visible,
            'created_by'      => $userId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Duplicate child blocks for column layouts
        $children = DB::table('openric_landing_block')
            ->where('parent_block_id', $blockId)
            ->get();

        foreach ($children as $child) {
            DB::table('openric_landing_block')->insert([
                'page_id'         => $child->page_id,
                'block_type_id'   => $child->block_type_id,
                'parent_block_id' => $newId,
                'column_slot'     => $child->column_slot,
                'config'          => $child->config,
                'title'           => $child->title,
                'css_classes'     => $child->css_classes,
                'container_type'  => $child->container_type,
                'background_color' => $child->background_color,
                'text_color'      => $child->text_color,
                'padding_top'     => $child->padding_top,
                'padding_bottom'  => $child->padding_bottom,
                'col_span'        => $child->col_span,
                'position'        => $child->position,
                'is_visible'      => $child->is_visible,
                'created_by'      => $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return ['success' => true, 'block_id' => $newId];
    }

    public function toggleBlockVisibility(int $blockId, int $userId): array
    {
        $block = DB::table('openric_landing_block')->where('id', $blockId)->first();

        if (!$block) {
            return ['success' => false, 'error' => 'Block not found'];
        }

        $newVisibility = !$block->is_visible;

        DB::table('openric_landing_block')->where('id', $blockId)->update([
            'is_visible'  => $newVisibility,
            'updated_by'  => $userId,
            'updated_at'  => now(),
        ]);

        return ['success' => true, 'is_visible' => $newVisibility];
    }

    // ── Versioning ───────────────────────────────────────────────────────

    public function getPageVersions(int $pageId): Collection
    {
        return DB::table('openric_landing_page_version')
            ->where('page_id', $pageId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function createVersion(int $pageId, string $status, int $userId): array
    {
        $page = $this->getPage($pageId);
        if (!$page) {
            return ['success' => false, 'error' => 'Page not found'];
        }

        $blocks = $this->getPageBlocks($pageId, false);

        $maxVersion = DB::table('openric_landing_page_version')
            ->where('page_id', $pageId)
            ->max('version_number') ?? 0;

        $versionId = DB::table('openric_landing_page_version')->insertGetId([
            'page_id'        => $pageId,
            'version_number' => $maxVersion + 1,
            'status'         => $status,
            'snapshot'       => json_encode([
                'page'   => (array) $page,
                'blocks' => $blocks->toArray(),
            ], JSON_THROW_ON_ERROR),
            'created_by'     => $userId,
            'created_at'     => now(),
        ]);

        return ['success' => true, 'version_id' => $versionId];
    }

    // ── User Dashboards ──────────────────────────────────────────────────

    public function getUserDashboards(int $userId): Collection
    {
        return DB::table('openric_landing_page')
            ->where('created_by', $userId)
            ->where('page_type', 'dashboard')
            ->orderBy('name')
            ->get();
    }

    // ── Statistics ────────────────────────────────────────────────────────

    public function getStats(): array
    {
        $counts = [];

        $entityTables = [
            'RecordResource' => 'record_resources',
            'Agent'          => 'agents',
            'Place'          => 'places',
            'Activity'       => 'activities',
            'Instantiation'  => 'instantiations',
        ];

        foreach ($entityTables as $label => $table) {
            if (Schema::hasTable($table)) {
                $counts[$label] = DB::table($table)->count();
            } else {
                $counts[$label] = 0;
            }
        }

        // Fallback: count from audit log
        if (array_sum($counts) === 0 && Schema::hasTable('audit_logs')) {
            $counts['Total Entities'] = DB::table('audit_logs')
                ->where('action', 'create')
                ->distinct('entity_iri')
                ->count('entity_iri');
        }

        // Recent additions
        $recent = [];
        if (Schema::hasTable('audit_logs')) {
            $recent = DB::table('audit_logs')
                ->where('action', 'create')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['entity_iri', 'entity_type', 'summary', 'created_at'])
                ->toArray();
        }

        return [
            'counts' => $counts,
            'recent' => $recent,
        ];
    }
}
