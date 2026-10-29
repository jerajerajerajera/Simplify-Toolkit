<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The mod loader and Minecraft version of a server, when its egg does not say.
 *
 * @property int $server_id
 * @property string $loader
 * @property string $minecraft
 */
final class ServerChoice extends Model
{
    protected $table = 'simplify_toolkit_servers';

    protected $guarded = ['id'];
}
