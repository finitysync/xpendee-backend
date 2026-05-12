<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'email',
        'plan',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}