<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_consultas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('dominio', 253);
            $table->string('tipo', 8);
            $table->string('status', 30)->default('pendente');

            $table->unsignedInteger('quantidade')->nullable();
            $table->json('registros')->nullable();
            $table->text('erro')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_consultas');
    }
};
