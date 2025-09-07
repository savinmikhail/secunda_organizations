<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'building_id',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function phones()
    {
        return $this->hasMany(OrganizationPhone::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_organization');
    }
}
