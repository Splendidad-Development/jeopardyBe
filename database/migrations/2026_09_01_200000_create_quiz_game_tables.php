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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->text('question_text');
            $table->text('answer')->nullable();
            $table->integer('points')->default(100);
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();

            $table->index(['section_id', 'order']);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color_code')->nullable();
            $table->timestamps();
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique();
            $table->string('status')->default('not_started');
            $table->foreignId('current_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('current_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('current_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedInteger('current_question_number')->default(0);
            $table->unsignedInteger('total_questions_answered')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('current_question_started_at')->nullable();
            $table->timestamp('current_question_expires_at')->nullable();
            $table->unsignedInteger('timer_duration_seconds')->default(30);
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('game_session_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('team_order')->default(1); // 1 = Team 1, 2 = Team 2
            $table->integer('score')->default(0);
            $table->timestamps();

            $table->unique(['game_session_id', 'team_id']);
            $table->unique(['game_session_id', 'team_order']);
        });

        Schema::create('game_session_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->json('final_scores');
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedInteger('total_questions_completed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->integer('score_change');
            $table->integer('previous_score');
            $table->integer('new_score');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_logs');
        Schema::dropIfExists('game_session_histories');
        Schema::dropIfExists('game_session_teams');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('sections');
    }
};
