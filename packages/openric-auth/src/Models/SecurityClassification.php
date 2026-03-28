<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityClassification extends Model
{
    protected $table = 'security_classifications';

    protected $fillable = [
        'code',
        'name',
        'level',
        'color',
        'active',
        'requires_2fa',
        'watermark_required',
        'download_allowed',
        'print_allowed',
        'copy_allowed',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'requires_2fa' => 'boolean',
            'watermark_required' => 'boolean',
            'download_allowed' => 'boolean',
            'print_allowed' => 'boolean',
            'copy_allowed' => 'boolean',
        ];
    }
}
