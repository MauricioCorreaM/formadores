<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'dane_code'];

    public function municipalities(): BelongsToMany
    {
        return $this->belongsToMany(Municipality::class);
    }

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
