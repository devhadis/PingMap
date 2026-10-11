<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    protected $table = 'diagnosticos';

    protected $fillable = [
        'user_id',
        'destino',
        'status',
        'perda_percentual',
        'latencia_media_ms',
        'latencia_minima_ms',
        'latencia_maxima_ms',
        'jitter_ms',
        'amostras',
        'erro',
    ];

    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
