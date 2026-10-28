<?php

declare(strict_types=1);

namespace Simplify\Mods\Models;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A plugin or mod installed through Simplify Mods.
 *
 * @property int $id
 * @property int $server_id
 * @property string $key "source:id", such as "modrinth:AANobbMI"
 * @property string $source
 * @property string $directory "/plugins" or "/mods"
 * @property string $filename the file name without ".disabled"
 * @property string $title
 * @property string $version
 * @property string $version_id
 * @property string $icon
 * @property string $page
 * @property bool $fits
 */
final class Package extends Model
{
    protected $table = 'simplify_mods_packages';

    protected $guarded = ['id'];

    protected $casts = ['fits' => 'boolean'];

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
