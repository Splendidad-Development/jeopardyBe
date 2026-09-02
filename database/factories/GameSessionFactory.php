<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    protected $model = GameSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_code' => 'GAME-'.strtoupper(Str::random(6)),
            'status' => GameStatus::NotStarted,
            'current_section_id' => null,
            'current_question_id' => null,
            'current_team_id' => null,
            'current_question_number' => 0,
            'total_questions_answered' => 0,
            'timer_duration_seconds' => 30,
        ];
    }
}
