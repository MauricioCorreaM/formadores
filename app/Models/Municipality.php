<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipality extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'dane_code', 'department_id', 'secretaria_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(Secretaria::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
