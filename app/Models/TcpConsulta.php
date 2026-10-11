<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TcpConsulta extends Model
{
    protected $table = 'tcp_consultas';

    protected $fillable = [
        'user_id',
        'destino',
        'porta',
        'status',
        'tempo_ms',
        'erro',
    ];

    protected function casts(): array
    {
        return [
            'porta' => 'integer',
            'tempo_ms' => 'decimal:3',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
