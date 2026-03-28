<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMINISTRATOR = 'administrator';
    public const EDITOR = 'editor';
    public const CONTRIBUTOR = 'contributor';
    public const TRANSLATOR = 'translator';

    public const ADMINISTRATOR_LEVEL = 100;
    public const EDITOR_LEVEL = 101;
    public const CONTRIBUTOR_LEVEL = 102;
    public const TRANSLATOR_LEVEL = 103;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'label',
        'level',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withPivot('grant_type')
            ->withTimestamps();
    }
}
