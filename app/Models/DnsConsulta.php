<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsConsulta extends Model
{
    protected $table = 'dns_consultas';

    protected $fillable = [
        'user_id',
        'dominio',
        'tipo',
        'status',
        'quantidade',
        'registros',
        'erro',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'registros' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
