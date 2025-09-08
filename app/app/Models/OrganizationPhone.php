<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPhone whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrganizationPhone extends Model
{
    protected $fillable = [
        'organization_id',
        'phone',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
