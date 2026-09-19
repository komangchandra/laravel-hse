<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $attributes = [
        'is_active' => true,
        'session_version' => 1,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'partner_id',
        'is_active',
        'deactivated_at',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'session_version' => 'integer',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    public function ownerOrganizationId(): ?int
    {
        if (! $this->partner) {
            return null;
        }

        return $this->partner->isOwner() ? $this->partner->id : $this->partner->owner_id;
    }

    /** @return list<int> */
    public function accessibleOrganizationIds(): array
    {
        if (! $this->partner) {
            return [];
        }

        if ($this->partner->isPartner()) {
            return [$this->partner->id];
        }

        return Partner::forOwner($this->partner)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function canAccessOrganizationId(?int $partnerId): bool
    {
        return $this->isDeveloper() || ($partnerId !== null && in_array($partnerId, $this->accessibleOrganizationIds(), true));
    }

    public function hasValidOrganizationRole(): bool
    {
        $roles = $this->getRoleNames();

        if ($roles->contains('developer')) {
            return $roles->count() === 1 && $this->partner_id === null;
        }

        if (! $this->partner || $roles->isEmpty()) {
            return false;
        }

        if ($this->partner->isPartner()) {
            return $roles->count() === 1 && $roles->contains('safety_mitra') && $this->partner->owner_id !== null;
        }

        return $roles->every(fn (string $role) => in_array($role, ['hse_owner', 'ktt'], true));
    }

    public function scopeVisibleTo(Builder $query, self $actor): Builder
    {
        if ($actor->isDeveloper()) {
            return $query;
        }

        return $query->whereIn('partner_id', $actor->accessibleOrganizationIds());
    }
}
