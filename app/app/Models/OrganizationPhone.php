<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
