<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GameSessionHistory;
use App\Models\GameSessionTeam;
use App\Models\Team;

class LeaderboardService
{
    /**
     * Get the leaderboard and rankings for a single game session.
     *
     * @return array{
     *     session_id: int,
     *     session_code: string,
     *     status: string,
     *     is_completed: bool,
     *     standings: array<int, array{
     *         rank: int,
     *         team_id: int,
     *         team_name: string,
     *         score: int,
     *         is_tie: bool,
     *         is_winner: bool
     *     }>
     * }
     */
    public function getGameSessionLeaderboard(GameSession $session): array
    {
        $sessionTeams = $session->sessionTeams()
            ->with('team')
            ->get()
            ->sortByDesc('score')
            ->values();

        $standings = [];
        $currentRank = 1;
        $previousScore = null;
        $isFirst = true;

        // Group by score to identify ties
        $scoreCounts = $sessionTeams->groupBy('score')->map->count();

        foreach ($sessionTeams as $index => $st) {
            if (! $isFirst && $st->score < $previousScore) {
                $currentRank = $index + 1;
            }

            $hasTie = ($scoreCounts->get($st->score, 0) > 1);
            $isWinner = ($currentRank === 1 && ! $hasTie);

            $standings[] = [
                'rank' => $currentRank,
                'team_id' => $st->team_id,
                'team_name' => $st->team->name,
                'score' => $st->score,
                'is_tie' => $hasTie,
                'is_winner' => $isWinner,
            ];

            $previousScore = $st->score;
            $isFirst = false;
        }

        return [
            'session_id' => $session->id,
            'session_code' => $session->session_code,
            'status' => $session->status->value,
            'is_completed' => $session->status->value === 'completed',
            'standings' => $standings,
        ];
    }

    /**
     * Get the global/all-time leaderboard across completed games.
     *
     * @return array<int, array{
     *     rank: int,
     *     team_id: int,
     *     team_name: string,
     *     total_score: int,
     *     games_played: int,
     *     wins: int,
     *     is_tie: bool
     * }>
     */
    public function getGlobalLeaderboard(): array
    {
        $teams = Team::all();
        $records = [];

        foreach ($teams as $team) {
            $sessionTeams = GameSessionTeam::where('team_id', $team->id)->get();
            $totalScore = $sessionTeams->sum('score');
            $gamesPlayed = $sessionTeams->count();

            // Count wins from history and completed sessions
            $historyWins = GameSessionHistory::where('winner_team_id', $team->id)->count();

            $records[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'total_score' => (int) $totalScore,
                'games_played' => $gamesPlayed,
                'wins' => $historyWins,
            ];
        }

        $sorted = collect($records)->sortByDesc('total_score')->values();

        $leaderboard = [];
        $currentRank = 1;
        $previousScore = null;
        $scoreCounts = $sorted->groupBy('total_score')->map->count();

        foreach ($sorted as $index => $item) {
            if ($index > 0 && $item['total_score'] < $previousScore) {
                $currentRank = $index + 1;
            }

            $isTie = ($scoreCounts->get($item['total_score'], 0) > 1);

            $leaderboard[] = array_merge($item, [
                'rank' => $currentRank,
                'is_tie' => $isTie,
            ]);

            $previousScore = $item['total_score'];
        }

        return $leaderboard;
    }
}
