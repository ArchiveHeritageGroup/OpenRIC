<?php

declare(strict_types=1);

namespace OpenRiC\Core\Traits;

use Illuminate\Support\Str;

/**
 * Trait that automatically assigns a UUID to the model's 'uuid' attribute
 * during the 'creating' Eloquent event.
 *
 * Usage: add `use HasUuid;` to any Eloquent model that has a 'uuid' column.
 * The trait registers a 'creating' observer so the UUID is set before the
 * first INSERT — it will not overwrite an explicitly provided value.
 */
trait HasUuid
{
    /**
     * Boot the trait and register the creating event listener.
     */
    public static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            $column = $model->getUuidColumn();

            if (empty($model->{$column})) {
                $model->{$column} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the name of the UUID column.
     *
     * Override this method in the model to use a different column name.
     */
    public function getUuidColumn(): string
    {
        return 'uuid';
    }
}
