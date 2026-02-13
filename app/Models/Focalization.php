<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Focalization extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class);
    }
}
