<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcp_consultas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('destino', 45);
            $table->unsignedSmallInteger('porta');
            $table->string('status', 30)
                ->default('pendente');

            $table->decimal('tempo_ms', 10, 3)
                ->nullable();

            $table->text('erro')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcp_consultas');
    }
};
