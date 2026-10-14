<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function diagnosticos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Diagnostico::class);
    }

    public function dnsConsultas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\DnsConsulta::class,
            'user_id'
        );
    }

    public function tcpConsultas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\TcpConsulta::class,
            'user_id'
        );
    }

    public function tracerouteConsultas():
        \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\TracerouteConsulta::class,
            'user_id'
        );
    }


    public function mtrConsultas():
        \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\MtrConsulta::class,
            'user_id'
        );
    }


}
