<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->decimal('latencia_minima_ms', 10, 3)
                ->nullable();

            $table->decimal('latencia_maxima_ms', 10, 3)
                ->nullable();

            $table->decimal('jitter_ms', 10, 3)
                ->nullable();

            $table->unsignedInteger('amostras')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->dropColumn([
                'latencia_minima_ms',
                'latencia_maxima_ms',
                'jitter_ms',
                'amostras',
            ]);
        });
    }
};
