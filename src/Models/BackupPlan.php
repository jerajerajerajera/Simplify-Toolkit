<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Models;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A server's automatic backups, or with server_id null the panel's own: on or paused, and what new servers get.
 *
 * @property int|null $server_id
 * @property bool $enabled
 * @property int $every_minutes
 * @property int $keep
 * @property bool $only_when_used
 * @property int $last_attempt
 * @property int $last_active
 * @property int $last_probe
 * @property int $pruned_at
 */
final class BackupPlan extends Model
{
    protected $table = 'simplify_toolkit_backups';

    protected $guarded = ['id'];

    protected $casts = ['enabled' => 'boolean', 'only_when_used' => 'boolean'];

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
