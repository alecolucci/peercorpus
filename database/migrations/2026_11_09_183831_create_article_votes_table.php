<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('article_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vote'); // 'included' ou 'excluded'
            $table->boolean('is_resolution')->default(false); // Define se é um voto de desempate do admin
            $table->timestamps();
            
            // Garante que um usuário só dê um voto normal e/ou um voto de resolução por artigo
            $table->unique(['article_id', 'user_id', 'is_resolution']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_votes');
    }
};
