<?php

namespace App\Support\Search;

use Illuminate\Database\Eloquent\Builder;

final class LikeSearch
{
    public static function apply(Builder $query, string $column, string $search): Builder
    {
        $escaped = str_replace('!', '!!', $search);
        $escaped = str_replace(['%','_'], ['!%','!_'], $escaped);

        return $query->whereRaw("{$column} LIKE ? ESCAPE '!'", ['%' . $escaped . '%']);
    }
}
