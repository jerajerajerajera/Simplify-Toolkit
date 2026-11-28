<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * How the server cards count the players of an egg's servers, or with egg_id null the panel's own choice.
 *
 * @property int|null $egg_id
 * @property string $protocol
 * @property string $port
 * @property int $port_offset
 * @property string|null $port_variable
 * @property bool $use_alias
 */
final class PlayerCount extends Model
{
    protected $table = 'simplify_toolkit_player_counts';

    protected $guarded = ['id'];

    protected $casts = ['use_alias' => 'boolean', 'port_offset' => 'integer'];
}
