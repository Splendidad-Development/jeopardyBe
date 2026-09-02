<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GameSessionTeam;
use App\Models\ScoreLog;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScoreService
{
    /**
     * Retrieve scores for all teams in a game session.
     *
     * @return array<int, array{team_id: int, team_name: string, team_order: int, score: int}>
     */
    public function getScores(GameSession $session): array
    {
        return $session->sessionTeams()
            ->with('team')
            ->orderBy('team_order')
            ->get()
            ->map(fn (GameSessionTeam $st) => [
                'team_id' => $st->team_id,
                'team_name' => $st->team->name,
                'team_order' => $st->team_order,
                'score' => $st->score,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Set explicit score for a specific team in a game session.
     */
    public function setTeamScore(GameSession $session, Team|int $team, int $newScore, ?string $reason = null, ?int $questionId = null): GameSessionTeam
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        $sessionTeam = $session->sessionTeams()->where('team_id', $teamId)->first();
        if (! $sessionTeam) {
            throw new InvalidArgumentException("Team ID {$teamId} is not participating in this game session.");
        }

        return DB::transaction(function () use ($session, $sessionTeam, $newScore, $reason, $questionId) {
            $previousScore = $sessionTeam->score;
            $scoreChange = $newScore - $previousScore;

            $sessionTeam->update(['score' => $newScore]);

            ScoreLog::create([
                'game_session_id' => $session->id,
                'team_id' => $sessionTeam->team_id,
                'question_id' => $questionId ?? $session->current_question_id,
                'score_change' => $scoreChange,
                'previous_score' => $previousScore,
                'new_score' => $newScore,
                'reason' => $reason ?? 'Moderator manual score update',
            ]);

            return $sessionTeam->fresh('team');
        });
    }

    /**
     * Adjust score by delta (+/- points) for a specific team.
     */
    public function adjustTeamScore(GameSession $session, Team|int $team, int $delta, ?string $reason = null, ?int $questionId = null): GameSessionTeam
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        $sessionTeam = $session->sessionTeams()->where('team_id', $teamId)->first();
        if (! $sessionTeam) {
            throw new InvalidArgumentException("Team ID {$teamId} is not participating in this game session.");
        }

        $newScore = $sessionTeam->score + $delta;

        return DB::transaction(function () use ($session, $sessionTeam, $newScore, $delta, $reason, $questionId) {
            $previousScore = $sessionTeam->score;

            $sessionTeam->update(['score' => $newScore]);

            ScoreLog::create([
                'game_session_id' => $session->id,
                'team_id' => $sessionTeam->team_id,
                'question_id' => $questionId ?? $session->current_question_id,
                'score_change' => $delta,
                'previous_score' => $previousScore,
                'new_score' => $newScore,
                'reason' => $reason ?? ($delta >= 0 ? "Added {$delta} points" : 'Deducted '.abs($delta).' points'),
            ]);

            return $sessionTeam->fresh('team');
        });
    }

    /**
     * Batch update scores for multiple teams in a session.
     *
     * @param  array<int, int>  $teamScores  Key: team_id, Value: new score
     */
    public function setBulkScores(GameSession $session, array $teamScores, ?string $reason = null): array
    {
        $results = [];
        foreach ($teamScores as $teamId => $score) {
            $results[] = $this->setTeamScore($session, (int) $teamId, (int) $score, $reason);
        }

        return $results;
    }
}
