<?php

declare(strict_types=1);

namespace OpenRiC\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Plugin registry model.
 *
 * Adapted from Heratio AhgCore\Models\AtomPlugin.
 * Renamed from AtomPlugin to CorePlugin for OpenRiC namespace.
 */
class CorePlugin extends Model
{
    protected $table = 'core_plugin';

    public $timestamps = true;

    /** @var string[] */
    protected $fillable = [
        'name',
        'class_name',
        'version',
        'description',
        'category',
        'is_enabled',
        'is_core',
        'is_locked',
        'load_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_core' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Scope: only enabled plugins.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: only core plugins.
     */
    public function scopeCore(Builder $query): Builder
    {
        return $query->where('is_core', true);
    }
}
