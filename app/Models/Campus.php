<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'dane_code', 'zone', 'school_id'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function focalizations(): BelongsToMany
    {
        return $this->belongsToMany(Focalization::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campus_user')
            ->withPivot('focalization_id');
    }
}
