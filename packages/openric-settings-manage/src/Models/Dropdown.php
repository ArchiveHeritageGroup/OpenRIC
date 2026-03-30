<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dropdown extends Model
{
    protected $fillable = [
        'code',
        'name',
        'module',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(DropdownOption::class)->orderBy('sort_order');
    }

    public function activeOptions(): HasMany
    {
        return $this->hasMany(DropdownOption::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}