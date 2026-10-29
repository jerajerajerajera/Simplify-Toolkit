<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The key of an outside service for the whole panel (user_id null) or for one user. Stored encrypted, never shown.
 *
 * @property int|null $user_id
 * @property string $service
 * @property string $secret
 */
final class ApiKey extends Model
{
    protected $table = 'simplify_toolkit_keys';

    protected $guarded = ['id'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted'];
    }
}
