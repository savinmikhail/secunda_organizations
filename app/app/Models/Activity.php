<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Activity> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read Activity|null $parent
 * @method static \Database\Factories\ActivityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'level',
    ];

    public function parent()
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Activity::class, 'parent_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'activity_organization');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Normalize parent_id
            if ($model->parent_id === 0) {
                $model->parent_id = null;
            }

            if ($model->parent_id) {
                $parent = static::query()->find($model->parent_id);
                if (! $parent) {
                    throw new \DomainException('Parent activity not found');
                }
                $model->level = (int) $parent->level + 1;
            } else {
                $model->level = 1;
            }

            if ($model->level < 1 || $model->level > 3) {
                throw new \DomainException('Activities depth limit (3) exceeded');
            }
        });
    }
}
