<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustScoreRequest;
use App\Http\Requests\UpdateScoreRequest;
use App\Models\GameSession;
use App\Services\ScoreService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ScoreController extends Controller
{
    public function __construct(
        protected ScoreService $scoreService
    ) {}

    /**
     * Retrieve current scores for a game session.
     */
    public function index(GameSession $game): JsonResponse
    {
        $scores = $this->scoreService->getScores($game);

        return response()->json([
            'game_id' => $game->id,
            'session_code' => $game->session_code,
            'scores' => $scores,
        ]);
    }

    /**
     * Update scores for one or more teams in the game session.
     */
    public function update(UpdateScoreRequest $request, GameSession $game): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'Moderator manual score update');
            $sessionTeams = $game->sessionTeams()->orderBy('team_order')->get();

            // Handle team1_score and/or team2_score
            if ($request->has('team1_score') && isset($sessionTeams[0])) {
                $this->scoreService->setTeamScore($game, $sessionTeams[0]->team_id, (int) $request->input('team1_score'), $reason);
            }
            if ($request->has('team2_score') && isset($sessionTeams[1])) {
                $this->scoreService->setTeamScore($game, $sessionTeams[1]->team_id, (int) $request->input('team2_score'), $reason);
            }

            // Handle specific team_id + score
            if ($request->filled('team_id') && $request->filled('score')) {
                $this->scoreService->setTeamScore($game, (int) $request->input('team_id'), (int) $request->input('score'), $reason);
            }

            // Handle team_scores map [team_id => score]
            if ($request->isJson() && $request->filled('team_scores')) {
                $this->scoreService->setBulkScores($game, (array) $request->input('team_scores'), $reason);
            }

            $currentScores = $this->scoreService->getScores($game);

            return response()->json([
                'message' => 'Scores updated successfully.',
                'game_id' => $game->id,
                'session_code' => $game->session_code,
                'scores' => $currentScores,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Adjust score by delta (+/- points) for a team.
     */
    public function adjust(AdjustScoreRequest $request, GameSession $game): JsonResponse
    {
        try {
            $teamId = (int) $request->input('team_id');
            $delta = (int) $request->input('delta');
            $reason = $request->input('reason');

            $updatedSessionTeam = $this->scoreService->adjustTeamScore($game, $teamId, $delta, $reason);
            $currentScores = $this->scoreService->getScores($game);

            return response()->json([
                'message' => 'Score adjusted successfully.',
                'team_id' => $updatedSessionTeam->team_id,
                'team_name' => $updatedSessionTeam->team?->name,
                'new_score' => $updatedSessionTeam->score,
                'scores' => $currentScores,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Get score audit logs for the game session.
     */
    public function logs(GameSession $game): JsonResponse
    {
        $logs = $game->scoreLogs()->with(['team', 'question'])->latest()->get();

        return response()->json([
            'game_id' => $game->id,
            'session_code' => $game->session_code,
            'logs' => $logs->map(fn ($log) => [
                'id' => $log->id,
                'team_id' => $log->team_id,
                'team_name' => $log->team?->name,
                'question_id' => $log->question_id,
                'question_text' => $log->question?->question_text,
                'score_change' => $log->score_change,
                'previous_score' => $log->previous_score,
                'new_score' => $log->new_score,
                'reason' => $log->reason,
                'created_at' => $log->created_at?->toISOString(),
            ]),
        ]);
    }
}
