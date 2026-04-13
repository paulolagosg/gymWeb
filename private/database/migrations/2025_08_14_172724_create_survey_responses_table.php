<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('survey_id')->constrained();
            $table->string('training_time')->nullable(); // Q2
            $table->integer('nps_score')->nullable(); // Q3
            $table->text('nps_reason')->nullable(); // Q4
            $table->json('servqual_ratings')->nullable(); // Q5-Q9
            $table->json('open_answers')->nullable(); // Q10-Q15

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
