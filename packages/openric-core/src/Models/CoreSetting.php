<?php

declare(strict_types=1);

namespace OpenRiC\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Core settings model — key/value with setting_group.
 *
 * Adapted from Heratio AhgCore\Models\AhgSetting.
 * Renamed from AhgSetting to CoreSetting for OpenRiC namespace.
 */
class CoreSetting extends Model
{
    protected $table = 'core_settings';

    public $timestamps = true;

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /** @var string[] */
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'setting_group',
        'description',
        'is_sensitive',
        'updated_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(QubitUser::class, 'updated_by');
    }

    /**
     * Get a setting value by key.
     *
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();

        return $setting ? $setting->setting_value : $default;
    }
}
