<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\GameSession;
use App\Models\GameSessionHistory;
use App\Models\Question;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GameProgressionService
{
    /**
     * Create and initialize a new game session.
     *
     * @param  array{session_code?: string, team1_name?: string, team2_name?: string, timer_duration_seconds?: int}  $data
     */
    public function createSession(array $data = []): GameSession
    {
        return DB::transaction(function () use ($data) {
            $sessionCode = $data['session_code'] ?? 'GAME-'.strtoupper(Str::random(6));
            $timerDuration = $data['timer_duration_seconds'] ?? config('game.timer_duration_seconds', 30);

            $session = GameSession::create([
                'session_code' => $sessionCode,
                'status' => GameStatus::NotStarted,
                'timer_duration_seconds' => $timerDuration,
            ]);

            $team1Name = $data['team1_name'] ?? 'Team 1';
            $team2Name = $data['team2_name'] ?? 'Team 2';

            $team1 = Team::firstOrCreate(['name' => $team1Name], ['color_code' => '#2563eb']);
            $team2 = Team::firstOrCreate(['name' => $team2Name], ['color_code' => '#dc2626']);

            $session->sessionTeams()->create([
                'team_id' => $team1->id,
                'team_order' => 1,
                'score' => 0,
            ]);

            $session->sessionTeams()->create([
                'team_id' => $team2->id,
                'team_order' => 2,
                'score' => 0,
            ]);

            return $session->fresh(['sessionTeams.team']);
        });
    }

    /**
     * Start the game session.
     */
    public function startGame(GameSession $session): GameSession
    {
        if ($session->status !== GameStatus::NotStarted) {
            throw new RuntimeException("Game cannot be started because it is already {$session->status->value}.");
        }

        $firstSection = Section::where('is_active', true)->orderBy('order')->first();
        if (! $firstSection) {
            throw new RuntimeException('Cannot start game: No active sections found.');
        }

        $firstQuestion = Question::where('section_id', $firstSection->id)->orderBy('order')->first();
        if (! $firstQuestion) {
            throw new RuntimeException("Cannot start game: Section '{$firstSection->title}' has no questions.");
        }

        $firstSessionTeam = $session->sessionTeams()->orderBy('team_order')->first();
        if (! $firstSessionTeam) {
            throw new RuntimeException('Cannot start game: No teams participating in this game session.');
        }

        $now = Carbon::now();

        $session->update([
            'status' => GameStatus::InProgress,
            'current_section_id' => $firstSection->id,
            'current_question_id' => $firstQuestion->id,
            'current_team_id' => $firstSessionTeam->team_id,
            'current_question_number' => 1,
            'total_questions_answered' => 0,
            'started_at' => $now,
            'ended_at' => null,
            'current_question_started_at' => $now,
            'current_question_expires_at' => $now->copy()->addSeconds($session->timer_duration_seconds),
        ]);

        return $session->fresh(['currentSection', 'currentQuestion', 'currentTeam', 'sessionTeams.team']);
    }

    /**
     * Move to the next question or section, or complete the game.
     */
    public function nextQuestion(GameSession $session): GameSession
    {
        if ($session->status !== GameStatus::InProgress) {
            throw new RuntimeException("Cannot advance question: Game session is not in progress (current status: {$session->status->value}).");
        }

        $currentQuestion = $session->currentQuestion;
        $currentSection = $session->currentSection;

        if (! $currentQuestion || ! $currentSection) {
            throw new RuntimeException('Inconsistent state: Game is in progress but has no active section or question.');
        }

        return DB::transaction(function () use ($session, $currentQuestion, $currentSection) {
            // Find next question in current section
            $nextQuestion = Question::where('section_id', $currentSection->id)
                ->where('order', '>', $currentQuestion->order)
                ->orderBy('order')
                ->first();

            $nextSection = $currentSection;
            $questionNumber = $session->current_question_number + 1;

            if (! $nextQuestion) {
                // Find next active section
                $nextSection = Section::where('is_active', true)
                    ->where('order', '>', $currentSection->order)
                    ->orderBy('order')
                    ->first();

                if ($nextSection) {
                    $nextQuestion = Question::where('section_id', $nextSection->id)
                        ->orderBy('order')
                        ->first();
                    $questionNumber = 1;
                }
            }

            // If no next question exists in any subsequent section, complete the game
            if (! $nextQuestion || ! $nextSection) {
                $session->update([
                    'status' => GameStatus::Completed,
                    'ended_at' => Carbon::now(),
                    'total_questions_answered' => $session->total_questions_answered + 1,
                    'current_question_started_at' => null,
                    'current_question_expires_at' => null,
                ]);

                return $session->fresh(['currentSection', 'currentQuestion', 'currentTeam', 'sessionTeams.team']);
            }

            // Determine next alternating team
            $teams = $session->sessionTeams()->orderBy('team_order')->get();
            $currentTeamIndex = $teams->search(fn ($item) => $item->team_id === $session->current_team_id);
            $nextTeamIndex = ($currentTeamIndex === false) ? 0 : ($currentTeamIndex + 1) % $teams->count();
            $nextTeam = $teams[$nextTeamIndex];

            $now = Carbon::now();

            $session->update([
                'current_section_id' => $nextSection->id,
                'current_question_id' => $nextQuestion->id,
                'current_team_id' => $nextTeam->team_id,
                'current_question_number' => $questionNumber,
                'total_questions_answered' => $session->total_questions_answered + 1,
                'current_question_started_at' => $now,
                'current_question_expires_at' => $now->copy()->addSeconds($session->timer_duration_seconds),
            ]);

            return $session->fresh(['currentSection', 'currentQuestion', 'currentTeam', 'sessionTeams.team']);
        });
    }

    /**
     * Restart the game session and optionally preserve historical game data.
     */
    public function restartGame(GameSession $session, bool $preserveHistory = true): GameSession
    {
        return DB::transaction(function () use ($session, $preserveHistory) {
            if ($preserveHistory && ($session->started_at !== null || $session->total_questions_answered > 0)) {
                $sessionTeams = $session->sessionTeams()->with('team')->get();
                $finalScores = [];
                $highestScore = PHP_INT_MIN;
                $winnerId = null;
                $isTie = false;

                foreach ($sessionTeams as $st) {
                    $teamName = $st->team?->name ?? "Team {$st->team_id}";
                    $finalScores[$teamName] = $st->score;

                    if ($st->score > $highestScore) {
                        $highestScore = $st->score;
                        $winnerId = $st->team_id;
                        $isTie = false;
                    } elseif ($st->score === $highestScore) {
                        $isTie = true;
                    }
                }

                GameSessionHistory::create([
                    'game_session_id' => $session->id,
                    'final_scores' => $finalScores,
                    'winner_team_id' => $isTie ? null : $winnerId,
                    'total_questions_completed' => $session->total_questions_answered,
                    'started_at' => $session->started_at,
                    'ended_at' => $session->ended_at ?? Carbon::now(),
                ]);
            }

            // Reset session
            $session->update([
                'status' => GameStatus::NotStarted,
                'current_section_id' => null,
                'current_question_id' => null,
                'current_team_id' => null,
                'current_question_number' => 0,
                'total_questions_answered' => 0,
                'started_at' => null,
                'ended_at' => null,
                'current_question_started_at' => null,
                'current_question_expires_at' => null,
            ]);

            // Reset team scores
            $session->sessionTeams()->update(['score' => 0]);

            return $session->fresh(['sessionTeams.team']);
        });
    }
}
