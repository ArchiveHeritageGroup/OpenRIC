<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurityClearance extends Model
{
    protected $table = 'user_security_clearance';

    protected $fillable = [
        'user_id',
        'classification_id',
        'granted_by',
        'granted_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(SecurityClassification::class, 'classification_id');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
