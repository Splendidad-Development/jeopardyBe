<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

    /**
     * Get leaderboard for a specific game session.
     */
    public function gameLeaderboard(GameSession $game): JsonResponse
    {
        $leaderboard = $this->leaderboardService->getGameSessionLeaderboard($game);

        return response()->json([
            'data' => $leaderboard,
        ]);
    }

    /**
     * Get global leaderboard across all games and teams.
     */
    public function globalLeaderboard(): JsonResponse
    {
        $leaderboard = $this->leaderboardService->getGlobalLeaderboard();

        return response()->json([
            'data' => $leaderboard,
        ]);
    }
}
