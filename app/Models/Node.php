<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }
}
