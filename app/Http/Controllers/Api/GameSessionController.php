<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGameSessionRequest;
use App\Http\Resources\CurrentQuestionResource;
use App\Http\Resources\GameSessionResource;
use App\Models\GameSession;
use App\Services\GameProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GameSessionController extends Controller
{
    public function __construct(
        protected GameProgressionService $progressionService
    ) {}

    /**
     * Display a listing of game sessions.
     */
    public function index(): AnonymousResourceCollection
    {
        $sessions = GameSession::with(['currentSection', 'currentQuestion', 'currentTeam', 'teams'])
            ->latest()
            ->paginate(15);

        return GameSessionResource::collection($sessions);
    }

    /**
     * Create and initialize a new game session.
     */
    public function store(StoreGameSessionRequest $request): JsonResponse
    {
        $session = $this->progressionService->createSession($request->validated());

        return (new GameSessionResource($session->load(['currentSection', 'currentQuestion', 'currentTeam', 'teams'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified game session.
     */
    public function show(GameSession $game): GameSessionResource
    {
        $game->load(['currentSection', 'currentQuestion', 'currentTeam', 'teams']);

        return new GameSessionResource($game);
    }

    /**
     * Start the game session.
     */
    public function start(GameSession $game): JsonResponse
    {
        try {
            $updatedSession = $this->progressionService->startGame($game);

            return response()->json([
                'message' => 'Game started successfully.',
                'data' => new GameSessionResource($updatedSession->load(['currentSection', 'currentQuestion', 'currentTeam', 'teams'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Retrieve the current question, assigned team, and timer status for the game session.
     */
    public function currentQuestion(GameSession $game): JsonResponse
    {
        $game->load(['currentSection', 'currentQuestion', 'currentTeam']);

        if ($game->status->value === 'not_started') {
            return response()->json([
                'message' => 'The game has not started yet.',
                'status' => $game->status->value,
                'data' => null,
            ], Response::HTTP_OK);
        }

        if ($game->status->value === 'completed') {
            return response()->json([
                'message' => 'The game has ended.',
                'status' => $game->status->value,
                'data' => null,
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => $game->status->value,
            'data' => new CurrentQuestionResource($game),
        ]);
    }

    /**
     * Advance to the next question or section, or mark the game as completed.
     */
    public function nextQuestion(GameSession $game): JsonResponse
    {
        try {
            $updatedSession = $this->progressionService->nextQuestion($game);

            $message = $updatedSession->status->value === 'completed'
                ? 'Game completed! All sections and questions have been finished.'
                : 'Advanced to next question.';

            return response()->json([
                'message' => $message,
                'data' => new GameSessionResource($updatedSession->load(['currentSection', 'currentQuestion', 'currentTeam', 'teams'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Restart the game session.
     */
    public function restart(Request $request, GameSession $game): JsonResponse
    {
        $preserveHistory = $request->boolean('preserve_history', true);

        $restartedSession = $this->progressionService->restartGame($game, $preserveHistory);

        return response()->json([
            'message' => 'Game reset and restarted successfully.',
            'data' => new GameSessionResource($restartedSession->load(['currentSection', 'currentQuestion', 'currentTeam', 'teams'])),
        ]);
    }
}
