<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    public const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'avatar',
        'bio',
        'website',
        'location',
        'is_onboarded',
        'is_admin',
        'is_verified',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_onboarded' => 'boolean',
            'is_admin' => 'boolean',
            'is_verified' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['username'] ?? $this->attributes['email'] ?? 'VDJ User');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function setPasswordHashAttribute(?string $value): void
    {
        if (! filled($value)) {
            return;
        }

        $this->attributes['password_hash'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
    }
}
