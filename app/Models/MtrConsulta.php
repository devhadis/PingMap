<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MtrConsulta extends Model
{
    protected $table = 'mtr_consultas';

    protected $fillable = [
        'user_id',
        'destino',
        'status',
        'saltos',
        'erro',
    ];

    protected function casts(): array
    {
        return [
            'saltos' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
