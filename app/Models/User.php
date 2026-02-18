<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'primary_node_id',
        'document_type',
        'document_number',
        'first_name',
        'second_name',
        'first_last_name',
        'second_last_name',
        'corregimiento',
        'birth_date',
        'sex_at_birth',
        'gender_identity',
        'sexual_orientation',
        'ethnic_belonging',
        'disability',
        'is_peasant',
        'is_migrant_population',
        'is_social_barra',
        'is_private_freedom_population',
        'is_human_rights_defender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'is_peasant' => 'boolean',
            'is_migrant_population' => 'boolean',
            'is_social_barra' => 'boolean',
            'is_private_freedom_population' => 'boolean',
            'is_human_rights_defender' => 'boolean',
        ];
    }

    public function primaryNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'primary_node_id');
    }

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class, 'campus_user')
            ->withPivot('focalization_id');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->hasRole('super_admin') || $this->hasRole('node_owner');
    }
}
