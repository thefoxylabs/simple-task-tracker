<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffGroup extends Model
{
    protected $fillable = ['name', 'description', 'color'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
